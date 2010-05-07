<?php

/** Representation of filtered table grouped by some column
*/
class NotORM_MultiResult extends NotORM_Result {
	private $result, $column, $active;
	
	function __construct($table, $result, $column, $active) {
		parent::__construct($table, $result->notORM);
		$this->result = $result;
		$this->column = $column;
		$this->active = $active;
	}
	
	function update(array $data) {
		$where = $this->where;
		$this->where[0] = "$this->column = " . $this->result->notORM->connection->quote($this->active);
		$return = parent::update($data);
		$this->where = $where;
		return $return;
	}
	
	function delete() {
		$where = $this->where;
		$this->where[0] = "$this->column = " . $this->result->notORM->connection->quote($this->active);
		$return = parent::delete();
		$this->where = $where;
		return $return;
	}
	
	function order($columns) {
		if (!$this->order) {
			$this->order[] = $this->column;
		}
		return parent::order($columns);
	}
	
	function group($functions, $having = "") {
		$query = "SELECT $functions, $this->column FROM $this->table"; // $this->column is last because result is used with list()
		if ($this->where) {
			$query .= " WHERE (" . implode(") AND (", $this->where) . ")";
		}
		$query .= " GROUP BY $this->column";
		if ($having != "") {
			$query .= " HAVING $having";
		}
		$aggregation = &$this->result->aggregation[$query];
		if (!isset($aggregation)) {
			$aggregation = array();
			foreach ($this->query($query, $this->parameters) as $row) {
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
				$limit = $this->limit;
				if ($this->limit) {
					$this->limit = null;
				}
				parent::execute();
				$this->limit = $limit;
				$referencing = array();
				$offset = array();
				foreach ($this->rows as $key => $row) {
					$ref = &$referencing[$row[$this->column]];
					$skip = &$offset[$row[$this->column]];
					if (!isset($limit) || (count($ref) < $limit && $skip >= $this->offset)) {
						$ref[$key] = $row;
					}
					$skip++;
					unset($ref, $skip);
				}
			}
			$this->data = &$referencing[$this->active];
			if (!isset($this->data)) {
				$this->data = array();
			}
		}
	}
	
}
