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
	function via($column) {
		$this->column = $column;
		return $this;
	}
	
	function insert($data) {
		$args = array();
		foreach (func_get_args() as $data) {
			if ($data instanceof Traversable && !$data instanceof NotORM_Result) {
				$data = iterator_to_array($data);
			}
			if (is_array($data)) {
				$data[$this->column] = $this->active;
			}
			$args[] = $data;
		}
		return call_user_func_array(array($this, 'parent::insert'), $args); // works since PHP 5.1.2, array('parent', 'insert') issues E_STRICT in 5.1.2 <= PHP < 5.3.0
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
	
	function select($columns) {
		$args = func_get_args();
		if (!$this->select) {
			$args[] = "$this->table.$this->column";
		}
		return call_user_func_array(array($this, 'parent::select'), $args);
	}
	
	function order($columns) {
		if (!$this->order) { // improve index utilization
			$this->order[] = "$this->table.$this->column" . (preg_match('~\\bDESC$~i', $columns) ? " DESC" : "");
		}
		$args = func_get_args();
		return call_user_func_array(array($this, 'parent::order'), $args);
	}
	
	function aggregation($function) {
		$join = $this->createJoins(implode(",", $this->conditions) . ",$function");
		$column = ($join ? "$this->table." : "") . $this->column;
		$query = "SELECT $function, $column FROM $this->table" . implode($join);
		if ($this->where) {
			$query .= " WHERE (" . implode(") AND (", $this->where) . ")";
		}
		$query .= " GROUP BY $column";
		$aggregation = &$this->result->aggregation[$query];
		if (!isset($aggregation)) {
			$aggregation = array();
			foreach ($this->query($query, $this->parameters) as $row) {
				$aggregation[$row[$this->column]] = $row;
			}
		}
		if (isset($aggregation[$this->active])) {
			foreach ($aggregation[$this->active] as $return) {
				return $return;
			}
		}
	}
	
	function count($column = "") {
		$return = parent::count($column);
		return (isset($return) ? $return : 0);
	}
	
	protected function execute() {
		if (!isset($this->rows)) {
			$referencing = &$this->result->referencing[$this->__toString()];
			if (!isset($referencing)) {
				$limit = $this->limit;
				$rows = count($this->result->rows);
				if ($this->limit && $rows > 1) {
					$this->limit = null;
				}
				parent::execute();
				$this->limit = $limit;
				$referencing = array();
				$offset = array();
				foreach ($this->rows as $key => $row) {
					$ref = &$referencing[$row[$this->column]];
					$skip = &$offset[$row[$this->column]];
					if (!isset($limit) || $rows <= 1 || (count($ref) < $limit && $skip >= $this->offset)) {
						$ref[$key] = $row;
					} else {
						unset($this->rows[$key]);
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
