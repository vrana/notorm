<?php declare(strict_types=1);

namespace NotORM;

/** Single row representation
* @implements \IteratorAggregate<string, mixed>
* @implements \ArrayAccess<string, mixed>
*/
class Row extends Base implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable {
	/** @var array<string, mixed> */ private array $modified = [];

	/** @access protected must be public because it is called from Result
	* @param array<string, mixed> $row
	*/
	function __construct(
		protected array $row,
		protected Result $result,
	) {
		$this->id = $this->primaryValue($row);
	}

	/** Get primary key value, the values of a multi-column primary key are separated by comma */
	function __toString(): string {
		$id = $this->primaryValue($this);
		return (is_array($id) ? implode(",", $id) : (string) $id); // (string) - PostgreSQL returns int
	}

	/** Get referenced row
	* @return Row|null null if the row does not exist
	*/
	function __get(string $name): ?Row {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		$value = $this[$column]; // re-executes the result with all columns if the column isn't in the cache
		$referenced = &$this->result->referenced[$name];
		if (!isset($referenced)) {
			$keys = [];
			foreach ($this->result->rows ?? [$this] as $row) { // rows are null after insert
				if ($row[$column] !== null) {
					$keys[$row[$column]] = null;
				}
			}
			if ($keys) {
				$table = $this->result->notORM->structure->getReferencedTable($name, $this->result->table);
				$referenced = new Result($table, $this->result->notORM);
				$referenced->where("$table." . $this->result->notORM->structure->getPrimary($table), array_keys($keys));
			} else {
				$referenced = [];
			}
		}
		return ($value === null ? null : $referenced[$value] ?? null); // referenced row may not exist
	}

	/** Test if referenced row exists */
	function __isset(string $name): bool {
		return ($this->__get($name) !== null);
	}

	/** Store referenced value */
	function __set(string $name, ?Row $value = null): void {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		$this[$column] = $value?->id;
	}

	/** Remove referenced column from data */
	function __unset(string $name): void {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		unset($this[$column]);
	}

	/** Get referencing rows
	* @param string $name table name
	* @param list<mixed> $args arguments passed to Result::where()
	*/
	function __call(string $name, array $args): MultiResult {
		$table = $this->result->notORM->structure->getReferencingTable($name, $this->result->table);
		$column = $this->result->notORM->structure->getReferencingColumn($table, $this->result->table);
		$return = new MultiResult($table, $this->result, $column, $this[$this->result->primary], $name);
		$return->where("$name.$column", ($this->result->rows !== null ? $this->result->ids() : [$this[$this->result->primary]])); // rows are null after insert
		if ($args) {
			$return->where(...$args);
		}
		return $return;
	}

	/** Update row
	* @param array<string, mixed>|null $data null for all modified values
	* @return int|false number of affected rows or false in case of an error
	*/
	function update(?array $data = null): int|false {
		// update is an SQL keyword
		$data ??= $this->modified;
		$result = new Result($this->result->table, $this->result->notORM);
		$return = $this->wherePrimary($result)->update($data);
		$this->id = $this->primaryValue($this);
		return $return;
	}

	/** Delete row
	* @return int|false number of affected rows or false in case of an error
	*/
	function delete(): int|false {
		// delete is an SQL keyword
		$result = new Result($this->result->table, $this->result->notORM);
		$return = $this->wherePrimary($result)->delete();
		$this->id = $this->primaryValue($this);
		return $return;
	}

	/** Get primary key value, [$column => $value] for a multi-column primary key
	* @param array<string, mixed>|Row $row
	*/
	private function primaryValue(array|Row $row): mixed {
		if (!is_array($this->result->primary)) {
			return $row[$this->result->primary] ?? null;
		}
		$return = [];
		foreach ($this->result->primary as $column) {
			$return[$column] = $row[$column] ?? null;
		}
		return $return;
	}

	/** Restrict $result to this row */
	private function wherePrimary(Result $result): Result {
		return (is_array($this->id) ? $result->where($this->id) : $result->where($this->result->primary, $this->id));
	}

	protected function access(string|int|null $key, bool $delete = false): void {
		if ($this->result->notORM->cache && ($key === null || !isset($this->modified[$key])) && $this->result->access($key, $delete)) {
			$id = $this->id ?? $this->row;
			$row = (is_array($id) ? $this->result->find($id) : $this->result[$id]);
			if ($this->id !== null && $row?->id != $this->id) { // the result is a list
				$row = $this->result->find($this->row);
			}
			$this->row = $row->row;
		}
	}

	// IteratorAggregate implementation

	function getIterator(): \Traversable {
		$this->access(null);
		return new \ArrayIterator($this->row);
	}

	// Countable implementation

	function count(): int {
		return count($this->row);
	}

	// ArrayAccess implementation

	/** Test if column exists
	* @param string $offset column name
	*/
	function offsetExists(mixed $offset): bool {
		$this->access($offset);
		$return = array_key_exists($offset, $this->row);
		if (!$return) {
			$this->access($offset, true);
		}
		return $return;
	}

	/** Get value of column
	* @param string $offset column name
	*/
	function offsetGet(mixed $offset): mixed {
		$this->access($offset);
		if (!array_key_exists($offset, $this->row)) {
			$this->access($offset, true);
		}
		return $this->row[$offset];
	}

	/** Store value in column
	* @param string|null $offset column name
	*/
	function offsetSet(mixed $offset, mixed $value): void {
		$this->row[$offset] = $value;
		$this->modified[$offset] = $value;
		foreach ($this->result->referenced as $name => $referenced) {
			if ($this->result->notORM->structure->getReferencedColumn($name, $this->result->table) == $offset) {
				unset($this->result->referenced[$name]); // loaded without the new value
			}
		}
	}

	/** Remove column from data
	* @param string $offset column name
	*/
	function offsetUnset(mixed $offset): void {
		unset($this->row[$offset]);
		unset($this->modified[$offset]);
	}

	// JsonSerializable implementation

	function jsonSerialize(): mixed {
		return $this->row;
	}

}
