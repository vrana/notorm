<?php
class SimpleRel_Result implements Iterator {
	private $table, $pdo, $simpleRel, $structure, $primary;
	private $select = array(), $where = array(), $parameters = array(), $order = array(), $limit = null, $offset = null;
	private $result, $row;
	
	/** Create table result
	* @param string
	* @param PDO
	* @param SimpleRel_Structure
	*/
	function __construct($table, SimpleRel $simpleRel, PDO $pdo, SimpleRel_Structure $structure) {
		$this->table = $table;
		$this->simpleRel = $simpleRel;
		$this->pdo = $pdo;
		$this->structure = $structure;
		$this->primary = $structure->getPrimary($table);
	}
	
	/** Get SQL query
	* @return string
	*/
	function __toString() {
		$return = "SELECT ";
		if ($this->select) {
			$return .= "$this->primary, " . implode(", ", $this->select);
		} else {
			$return .= "*";
		}
		$return .= "\nFROM $this->table";
		if ($this->where) {
			$return .= "\nWHERE " . implode(" AND ", $this->where);
		}
		if ($this->order) {
			$return .= "\nORDER BY " . implode(", ", $this->order);
		}
		if ($this->limit) {
			$return .= "\nLIMIT $this->limit"; //! driver specific
			if (isset($this->offset)) {
				$return .= " OFFSET $this->offset";
			}
		}
		return $return;
	}
	
	/** Set select clause, more calls appends to the end
	* @param string for example "column, MD5(column) AS column_md5"
	* @return SimpleRel_Result fluent interface
	*/
	function select($select) {
		$this->select[] = $select;
		return $this;
	}
	
	/** Set where condition, more calls appends with AND
	* @param string condition possibly containing ? or :name
	* @param mixed array accepted by PDOStatement::execute or a single value
	* @return SimpleRel_Result fluent interface
	*/
	function where($condition, $parameters = array()) {
		$this->where[] = $condition;
		if (!is_array($parameters)) {
			$parameters = array($parameters);
		}
		$this->parameters = array_merge($this->parameters, $parameters);
		return $this;
	}
	
	//! group by, having
	
	/** Set order clause, more calls appends to the end
	* @param string for example "column1, column2 DESC"
	* @return SimpleRel_Result fluent interface
	*/
	function order($order) {
		$this->order[] = $order;
		return $this;
	}
	
	/** Set limit clause, more calls rewrite old values
	* @param int
	* @param int
	* @return SimpleRel_Result fluent interface
	*/
	function limit($limit, $offset = null) {
		$this->limit = $limit;
		$this->offset = $offset;
		return $this;
	}
	
	/** Shortcut for first aggregation("COUNT($column)")
	* @param string
	* @return int
	*/
	function count($column = "*") {
		// this is not Countable implementation
		$row = $this->aggregation("COUNT($column)");
		return $row[0];
	}
	
	/** Execute aggregation functions
	* @param string for example "COUNT(*), MAX(id)"
	* @return array
	*/
	private function aggregation($function) {
		$query = "SELECT $function\nFROM $this->table";
		if ($this->where) {
			$query .= "\nWHERE " . implode(" AND ", $this->where);
		}
		$result = $this->pdo->prepare($query);
		$result->execute($this->parameters);
		return $result->fetch();
	}
	
	private function execute() {
		$this->result = $this->pdo->prepare($this->__toString());
		//~ echo $this->result->queryString;
		//~ print_r($this->parameters);
		$this->result->execute($this->parameters);
	}
	
	/** Fetch next row of result
	* @return SimpleRel_Row
	*/
	function fetch() {
		if (!isset($this->result)) {
			$this->execute();
		}
		$this->next();
		if (!$this->row) {
			return false;
		}
		return $this->current();
	}
	
	// Iterator implementation
	
	function rewind() {
		$this->execute(); // seek is impossible
		$this->next();
	}
	
	function current() {
		return new SimpleRel_Row($this->row, $this->primary, $this->table, $this->simpleRel, $this->structure);
	}
	
	function valid() {
		return $this->row;
	}
	
	function next() {
		$this->row = $this->result->fetch(PDO::FETCH_ASSOC);
	}
	
	function key() {
		return $this->row[$this->primary];
	}
}
