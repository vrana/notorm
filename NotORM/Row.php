<?php

/** Single row representation
*/
class NotORM_Row extends NotORM_Abstract implements IteratorAggregate, ArrayAccess {
	private $row, $result;
	
	protected function __construct(DibiRow $row, NotORM_Result $result) {
		$this->row = $row;
		$this->result = $result;
	}
	
	/** Get primary key value
	* @return string
	*/
	function __toString() {
		return (string) $this[$this->result->primary]; // (string) - PostgreSQL returns int
	}
	
	/** Get referenced row
	* @param string
	* @return NotORM_Row or null if the row does not exist
	*/
	function __get($name) {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		$referenced = &$this->result->referenced[$name];
		if (!isset($referenced)) {
			$table = $this->result->notORM->structure->getReferencedTable($name, $this->result->table);
			$keys = array();
			foreach ($this->result->rows as $row) {
				$keys[$row[$column]] = null;
			}
			$referenced = new NotORM_Result($table, $this->result->notORM);
			$referenced->where($this->result->notORM->structure->getPrimary($table), array_keys($keys));
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
	function __isset($name) {
		return ($this->__get($name) !== null);
	}
	
	// __set is not defined to allow storing custom references (undocumented)
	
	/** Get referencing rows
	* @param string table name
	* @param array (["condition"[, array("value")]])
	* @return NotORM_Result
	*/
	function __call($name, array $args) {
		$table = $this->result->notORM->structure->getReferencingTable($name, $this->result->table);
		$column = $this->result->notORM->structure->getReferencingColumn($table, $this->result->table);
		$return = new NotORM_MultiResult($table, $this->result, $column, $this[$this->result->primary]);
		$return->where($column, array_keys($this->result->rows));
		if ($args) {
			call_user_func_array(array($return, 'where'), $args);
		}
		return $return;
	}
	
	// IteratorAggregate implementation
	
	function getIterator() {
		return new ArrayIterator($this->row);
	}
	
	// ArrayAccess implementation
	
	function offsetExists($key) {
		return array_key_exists($key, $this->row);
	}
	
	function offsetGet($key) {
		return $this->row[$key];
	}
	
	function offsetSet($key, $value) {
		$this->row[$key] = $value;
	}
	
	function offsetUnset($key) {
		unset($this->row[$key]);
	}
	
}
