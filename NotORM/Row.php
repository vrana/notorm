<?php

/** Single row representation
*/
class NotORM_Row implements IteratorAggregate, ArrayAccess {
	private $row, $primary, $table, $result, $connection, $structure;
	
	function __construct($row, $primary, $table, NotORM_Result $result, $connection, NotORM_Structure $structure) {
		$this->row = $row;
		$this->primary = $primary;
		$this->table = $table;
		$this->result = $result;
		$this->connection = $connection;
		$this->structure = $structure;
	}
	
	/** Get primary key value
	* @return string
	*/
	function __toString() {
		return (string) $this->row[$this->primary]; // (string) - PostgreSQL returns int
	}
	
	/** Get referenced row
	* @param string
	* @return NotORM_Row or null if the row does not exist
	*/
	function __get($name) {
		$column = $this->structure->getReferencedColumn($name, $this->table);
		$referenced = &$this->result->referenced[$name];
		if (!isset($referenced)) {
			$table = $this->structure->getReferencedTable($name, $this->table);
			$keys = array();
			foreach ($this->result->getRows() as $row) {
				$keys[$row[$column]] = null;
			}
			$referenced = new NotORM_Result($table, $this->connection, $this->structure);
			$referenced->where($this->structure->getPrimary($table), array_keys($keys));
		}
		if (!isset($referenced[$this->row[$column]])) { // referenced row may not exist
			return null;
		}
		return $referenced[$this->row[$column]];
	}
	
	/** Get referencing rows
	* @param string table name
	* @param array (["condition"[, array("value")]])
	* @return NotORM_Result
	*/
	function __call($name, array $args) {
		$table = $this->structure->getReferencingTable($name, $this->table);
		$column = $this->structure->getReferencingColumn($table, $this->table);
		$return = new NotORM_MultiResult($table, $this->connection, $this->structure, $this->result, $column, $this->row[$this->primary]);
		$return->where($column, array_keys($this->result->getRows()));
		//~ $return->order($column); // to allow multi-column indexes
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
