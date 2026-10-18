<?php
namespace NotORM;

/** Single row representation
* @implements \IteratorAggregate<string, mixed>
* @implements \ArrayAccess<string, mixed>
*/
class Row extends Base implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable {
	private $modified = array();

	protected $row;
	protected $result;
	protected $primary;

	/** @access protected must be public because it is called from Result */
	function __construct(array $row, Result $result) {
		$this->row = $row;
		$this->result = $result;
		if (array_key_exists($result->primary, $row)) {
			$this->primary = $row[$result->primary];
		}
	}

	/** Get primary key value */
	function __toString(): string {
		return (string) $this[$this->result->primary]; // (string) - PostgreSQL returns int
	}

	/** Get referenced row
	* @return Row|null null if the row does not exist
	*/
	function __get(string $name): ?Row {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		$referenced = &$this->result->referenced[$name];
		if (!isset($referenced)) {
			$keys = array();
			foreach ($this->result->rows ?? array($this) as $row) { // rows are null after insert
				if ($row[$column] !== null) {
					$keys[$row[$column]] = null;
				}
			}
			if ($keys) {
				$table = $this->result->notORM->structure->getReferencedTable($name, $this->result->table);
				$referenced = new Result($table, $this->result->notORM);
				$referenced->where("$table." . $this->result->notORM->structure->getPrimary($table), array_keys($keys));
			} else {
				$referenced = array();
			}
		}
		if (!isset($referenced[$this[$column]])) { // referenced row may not exist
			return null;
		}
		return $referenced[$this[$column]];
	}

	/** Test if referenced row exists */
	function __isset(string $name): bool {
		return ($this->__get($name) !== null);
	}

	/** Store referenced value */
	function __set(string $name, ?Row $value = null): void {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		$this[$column] = $value;
	}

	/** Remove referenced column from data */
	function __unset(string $name): void {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		unset($this[$column]);
	}

	/** Get referencing rows
	* @param string $name table name
	* @param mixed[] $args (["condition"[, array("value")]])
	*/
	function __call(string $name, array $args): MultiResult {
		$table = $this->result->notORM->structure->getReferencingTable($name, $this->result->table);
		$column = $this->result->notORM->structure->getReferencingColumn($table, $this->result->table);
		$return = new MultiResult($table, $this->result, $column, $this[$this->result->primary]);
		$return->where("$table.$column", ($this->result->rows !== null ? array_keys($this->result->rows) : array($this[$this->result->primary]))); // rows are null after insert
		if ($args) {
			call_user_func_array(array($return, 'where'), $args);
		}
		return $return;
	}

	/** Update row
	* @param array<string, mixed>|null $data null for all modified values
	* @return int|false number of affected rows or false in case of an error
	*/
	function update(?array $data = null): int|false {
		// update is an SQL keyword
		if (!isset($data)) {
			$data = $this->modified;
		}
		$result = new Result($this->result->table, $this->result->notORM);
		$return = $result->where($this->result->primary, $this->primary)->update($data);
		$this->primary = $this[$this->result->primary];
		return $return;
	}

	/** Delete row
	* @return int|false number of affected rows or false in case of an error
	*/
	function delete(): int|false {
		// delete is an SQL keyword
		$result = new Result($this->result->table, $this->result->notORM);
		$return = $result->where($this->result->primary, $this->primary)->delete();
		$this->primary = $this[$this->result->primary];
		return $return;
	}

	protected function access($key, $delete = false): void {
		if ($this->result->notORM->cache && !isset($this->modified[$key]) && $this->result->access($key, $delete)) {
			$id = $this->primary ?? $this->row;
			$this->row = $this->result[$id]->row;
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
	* @param string $key column name
	*/
	function offsetExists($key): bool {
		$this->access($key);
		$return = array_key_exists($key, $this->row);
		if (!$return) {
			$this->access($key, true);
		}
		return $return;
	}

	/** Get value of column
	* @param string $key column name
	*/
	function offsetGet($key): mixed {
		$this->access($key);
		if (!array_key_exists($key, $this->row)) {
			$this->access($key, true);
		}
		return $this->row[$key];
	}

	/** Store value in column
	* @param string|null $key column name
	*/
	function offsetSet($key, mixed $value): void {
		$this->row[$key] = $value;
		$this->modified[$key] = $value;
	}

	/** Remove column from data
	* @param string $key column name
	*/
	function offsetUnset($key): void {
		unset($this->row[$key]);
		unset($this->modified[$key]);
	}

	// JsonSerializable implementation

	function jsonSerialize(): mixed {
		return $this->row;
	}

}
