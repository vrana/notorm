<?php
namespace NotORM;

/**
 * Class Row
 * Single row representation
 * @package NotORM
 */
class Row extends AbstractClass implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable {
	private $modified = array();
	protected $row, $result, $primary;

	/**
	 * Row constructor.
	 * @param array $row
	 * @param Result $result
	 */
	public function __construct(array $row, Result $result) {
		$this->row = $row;
		$this->result = $result;
		if (array_key_exists($result->primary, $row)) {
			$this->primary = $row[$result->primary];
		}
	}

	/** Get primary key value
	* @return string
	*/
	public function __toString() {
		return (string) $this[$this->result->primary]; // (string) - PostgreSQL returns int
	}

	/** Get referenced row
	* @param string
	* @return Row or null if the row does not exist
	*/
	public function __get($name) {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		$referenced = &$this->result->referenced[$name];
		if (!isset($referenced)) {
			$keys = array();
			foreach ($this->result->rows as $row) {
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

	/** Test if referenced row exists
	* @param string
	* @return bool
	*/
	public function __isset($name) {
		return ($this->__get($name) !== null);
	}

	/** Store referenced value
	 * @param $name
	 * @param Row $value
	 * @return null
	 * @internal param $string
	 * @internal param or $NotORM_Row null
	 */
	public function __set($name, Row $value = null) {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		$this[$column] = $value;
	}

	/** Remove referenced column from data
	* @param string
	* @return null
	*/
	public function __unset($name) {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		unset($this[$column]);
	}

	/** Get referencing rows
	 * @param $name
	 * @param array $args (["condition"[, array("value")]])
	 * @return MultiResult
	 */
	public function __call($name, array $args) {
		$table = $this->result->notORM->structure->getReferencingTable($name, $this->result->table);
		$column = $this->result->notORM->structure->getReferencingColumn($table, $this->result->table);
		$return = new MultiResult($table, $this->result, $column, $this[$this->result->primary]);
		$return->where("$table.$column", array_keys((array) $this->result->rows)); // (array) - is null after insert
		if ($args) {
			call_user_func_array(array($return, 'where'), $args);
		}
		return $return;
	}

	/** Update row
	* @param array|null $data for all modified values
	* @return int number of affected rows or false in case of an error
	*/
	public function update($data = null) {
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
	* @return int number of affected rows or false in case of an error
	*/
	public function delete() {
		// delete is an SQL keyword
		$result = new Result($this->result->table, $this->result->notORM);
		$return = $result->where($this->result->primary, $this->primary)->delete();
		$this->primary = $this[$this->result->primary];
		return $return;
	}

	protected function access($key, $delete = false) {
		if ($this->result->notORM->cache && !isset($this->modified[$key]) && $this->result->access($key, $delete)) {
			$id = (string) (isset($this->primary) ? $this->primary : $this->row);
			$this->row = $this->result[$id]->row;
		}
	}

	// IteratorAggregate implementation

	public function getIterator() {
		$this->access(null);
		return new \ArrayIterator($this->row);
	}

	// Countable implementation

	public function count() {
		return count($this->row);
	}

	// ArrayAccess implementation

	/** Test if column exists
	 * Param column $string name
	 * @param mixed $key
	 * @return bool
	 */
	public function offsetExists($key) {
		$this->access($key);
		$return = array_key_exists($key, $this->row);
		if (!$return) {
			$this->access($key, true);
		}
		return $return;
	}

	/** Get value of column
	 * Param column $string name
	 * @param mixed $key
	 * @return string
	 */
	public function offsetGet($key) {
		$this->access($key);
		if (!array_key_exists($key, $this->row)) {
			$this->access($key, true);
		}
		return $this->row[$key];
	}

	/** Store value in column
	 * @param mixed $key
	 * @param mixed $value
	 * @return null
	 * @internal param column $string name
	 */
	public function offsetSet($key, $value) {
		$this->row[$key] = $value;
		$this->modified[$key] = $value;
	}

	/** Remove column from data
	 * @param mixed $key
	 * @return null
	 * @internal param column $string name
	 */
	public function offsetUnset($key) {
		unset($this->row[$key]);
		unset($this->modified[$key]);
	}

	// JsonSerializable implementation

	public function jsonSerialize() {
		return $this->row;
	}

}
