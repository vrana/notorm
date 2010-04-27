<?php
class SimpleRel_Row implements IteratorAggregate, ArrayAccess {
	private $row, $primary, $table, $result, $pdo, $structure;
	private $rows = array();
	
	function __construct(array $row, $primary, $table, SimpleRel_Result $result, PDO $pdo, SimpleRel_Structure $structure) {
		$this->row = $row;
		$this->primary = $primary;
		$this->table = $table;
		$this->result = $result;
		$this->pdo = $pdo;
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
		$column = $this->structure->getForeignColumn($name, $this->table);
		if (!isset($this->rows[$name])) {
			$table = $this->structure->getForeignTable($name, $this->table);
			$keys = array();
			foreach ($this->result->getRows() as $row) {
				$keys[$this->pdo->quote($row[$column])] = null;
			}
			$in = implode(", ", array_keys($keys[$name]));
			$this->rows[$name] = new SimpleRel_Result($table, $this->pdo, $this->structure);
			$this->rows[$name]->where($this->structure->getPrimary($name) . " IN ($in)");
		}
		return $this->rows[$name][$this->row[$column]];
	}
	
	/** Get referencing rows
	* @param string table name 
	* @param array (["condition"[, array("value")]])
	* @return SimpleRel_Result
	*/
	function __call($name, array $args) {
		$table = $this->structure->getForeignTable($name, $this->table);
		$column = $this->structure->getForeignColumn($this->table, $table);
		$return = new SimpleRel_Result($table, $this->pdo, $this->structure);
		$return->where("$column = ?", array($this->row[$this->primary]));
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
