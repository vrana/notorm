<?php

/** Representation of filtered table grouped by some column
*/
class NotORM_MultiResult extends NotORM_Result {
	private $result, $column, $active, $data;
	
	function __construct($table, PDO $pdo, NotORM_Structure $structure, $result, $column, $active) {
		parent::__construct($table, $pdo, $structure);
		$this->result = $result;
		$this->column = $column;
		$this->active = $active;
	}
	
	function aggregation($function) {
		$query = "SELECT $function, $this->column FROM $this->table";
		if ($this->where) {
			$query .= " WHERE " . implode(" AND ", $this->where);
		}
		$query .= " GROUP BY $this->column";
		$aggregation = &$this->result->aggregation[$query];
		if (!isset($aggregation)) {
			$aggregation = array();
			$result = $this->pdo->prepare($query);
			//~ fwrite(STDERR, "$result->queryString\n");
			$result->execute($this->parameters);
			foreach ($result as $row) {
				$aggregation[$row[$this->column]] = $row;
			}
		}
		if (!isset($aggregation[$this->active])) {
			return array();
		}
		return $aggregation[$this->active];
	}
	
	protected function execute() {
		$referencing = &$this->result->referencing[$this->__toString()];
		if (!isset($referencing)) {
			parent::execute();
			$referencing = array();
			foreach ($this->rows as $key => $row) {
				$referencing[$row[$this->column]][$key] = $row;
			}
		}
		$this->data = &$referencing[$this->active];
		if (!isset($this->data)) {
			$this->data = array();
		}
	}
	
	// IteratorAggregate implementation
	
	function getIterator() {
		$this->execute();
		return new ArrayIterator($this->data);
	}
	
	// ArrayAccess implementation
	
	function offsetExists($key) {
		$this->execute();
		return isset($this->data[$key]);
	}
	
	function offsetGet($key) {
		$this->execute();
		return $this->data[$key];
	}
	
	function offsetSet($key, $value) {
		$this->execute();
		$this->data[$key] = $value;
	}
	
	function offsetUnset($key) {
		$this->execute();
		unset($this->data[$key]);
	}
	
}
