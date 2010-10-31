<?php

/** Representation of filtered table grouped by some column
*/
class NotORM_MultiResult extends NotORM_Result {
	private $result, $column, $active;
	
	/** @access protected must be public because it is called from Row */
	function __construct($table, NotORM_Result $result, $column, $active) {
		parent::__construct($table, $result->notORM);
		$this->result = $result;
		$this->column = $column;
		$this->active = $active;
	}
	
	/** Specify referencing column
	* @param string
	* @return NotORM_MultiResult fluent interface
	*/
	function through($column) {
		$this->column = $column;
		return $this;
	}
	
	function update(array $data) {
		$where = $this->where;
		$this->where[0] = "$this->column = " . $this->notORM->connection->quote($this->active);
		$return = parent::update($data);
		$this->where = $where;
		return $return;
	}
	
	function delete() {
		$where = $this->where;
		$this->where[0] = "$this->column = " . $this->notORM->connection->quote($this->active);
		$return = parent::delete();
		$this->where = $where;
		return $return;
	}
	
	function order($columns) {
		if (!$this->order) { // improve index utilization
			$this->order[] = "$this->table.$this->column" . (preg_match('~\\bDESC$~i', $columns) ? " DESC" : "");
		}
		return parent::order($columns);
	}
	
	function aggregation($function) {
		$query = "SELECT $function, $this->column FROM $this->table";
		if ($this->where) {
			$query .= " WHERE (" . implode(") AND (", $this->where) . ")";
		}
		$query .= " GROUP BY $this->column";
		$aggregation = &$this->result->aggregation[$query];
		if (!isset($aggregation)) {
			$aggregation = array();
			foreach ($this->query($query, $this->parameters) as $row) {
				$aggregation[$row[$this->column]] = $row;
			}
		}
		foreach ($aggregation[$this->active] as $val) {
			return $val;
		}
	}
	
	protected function execute() {
		if (!isset($this->rows)) {
			$limit = $this->limit;
			if ($this->limit) {
				$this->limit = null;
			}
			parent::execute();
			$this->limit = $limit;
			$referencing = &$this->result->referencing[$this->__toString()];
			if (!isset($referencing)) {
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
