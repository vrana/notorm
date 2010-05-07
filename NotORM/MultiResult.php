<?php

/** Representation of filtered table grouped by some column
*/
class NotORM_MultiResult extends NotORM_Result {
	private $result, $column, $active;
	
	function __construct($table, $connection, NotORM_Structure $structure, $result, $column, $active) {
		parent::__construct($table, $connection, $structure);
		$this->result = $result;
		$this->column = $column;
		$this->active = $active;
	}
	
	function group($functions, $having = "") {
		$query = "SELECT $functions, $this->column FROM $this->table"; // $this->column is last because result is used with list()
		if ($this->where) {
			$query .= " WHERE " . implode(" AND ", $this->where);
		}
		$query .= " GROUP BY $this->column";
		if ($having != "") {
			$query .= " HAVING $having";
		}
		$aggregation = &$this->result->aggregation[$query];
		if (!isset($aggregation)) {
			$aggregation = array();
			foreach ($this->query($query) as $row) {
				if ($this->connection instanceof DibiConnection) {
					$row = (array) $row + array_values((array) $row); // to allow list($min, $max)
				}
				$aggregation[$row[$this->column]] = $row;
			}
		}
		if (!isset($aggregation[$this->active])) {
			return array();
		}
		return $aggregation[$this->active];
	}
	
	protected function execute() {
		if (!isset($this->data)) {
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
	}
	
}
