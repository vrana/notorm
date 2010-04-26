<?php
class SimpleRel_Row implements IteratorAggregate, ArrayAccess {
	private $row, $primary, $table, $simpleRel, $structure;
	
	function __construct(array $row, $primary, $table, SimpleRel $simpleRel, SimpleRel_Structure $structure) {
		$this->row = $row;
		$this->primary = $primary;
		$this->table = $table;
		$this->simpleRel = $simpleRel;
		$this->structure = $structure;
	}
	
	/** Get primary key value
	* @return string
	*/
	function __toString() {
		return $this->row[$this->primary];
	}
	
	/** Get referenced row
	* @param string
	* @return SimpleRel_Row
	*/
	function __get($name) {
		$table = $this->structure->getForeignTable($name, $this->table);
		$column = $this->structure->getForeignColumn($name, $this->table);
		$where = array($this->structure->getPrimary($name) . " = ?", array($this->row[$column]));
		return $this->simpleRel->__call($table, $where)->fetch();
	}
	
	/** Get referencing rows
	* @param string table name 
	* @param array (["condition"[, array("value")]])
	* @return SimpleRel_Result
	*/
	function __call($name, array $args) {
		$table = $this->structure->getForeignTable($name, $this->table);
		$column = $this->structure->getForeignColumn($this->table, $table);
		return $this->simpleRel->__call($table, $args)->where("$column = ?", array($this->row[$this->primary]));
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
