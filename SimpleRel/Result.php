<?php
class SimpleRel_Result implements IteratorAggregate, ArrayAccess, Countable {
	private $table, $pdo, $structure, $single, $primary;
	private $select = array(), $where = array(), $parameters = array(), $order = array(), $limit = null, $offset = null;
	private $rows;
	
	/** Create table result
	* @param string
	* @param PDO
	* @param SimpleRel_Structure
	* @param bool single row
	*/
	function __construct($table, PDO $pdo, SimpleRel_Structure $structure, $single = false) {
		$this->table = $table;
		$this->pdo = $pdo;
		$this->structure = $structure;
		$this->single = $single;
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
		$return .= " FROM $this->table";
		if ($this->where) {
			$return .= " WHERE " . implode(" AND ", $this->where);
		}
		if ($this->order) {
			$return .= " ORDER BY " . implode(", ", $this->order);
		}
		if ($this->limit) {
			$return .= " LIMIT $this->limit"; //! driver specific
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
		if ($parameters && !strpbrk($condition, "?:")) {
			$condition .= " = ?";
		}
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
	
	/** Shortcut for "COUNT($column)"
	* @param string
	* @return int
	*/
	function count($column = "*") {
		$row = $this->aggregation("COUNT($column)");
		return $row[0];
	}
	
	/** Execute aggregation functions
	* @param string for example "COUNT(*), MAX(id)"
	* @return array using PDO::FETCH_BOTH
	*/
	private function aggregation($function) {
		$query = "SELECT $function FROM $this->table";
		if ($this->where) {
			$query .= " WHERE " . implode(" AND ", $this->where);
		}
		$result = $this->pdo->prepare($query);
		$result->execute($this->parameters);
		return $result->fetch(PDO::FETCH_BOTH);
	}
	
	private function execute() {
		if (!isset($this->rows)) {
			$result = $this->pdo->prepare($this->__toString());
			//~ echo $result->queryString;
			//~ print_r($this->parameters);
			$result->execute($this->parameters);
			$result->setFetchMode(PDO::FETCH_ASSOC);
			$this->rows = array();
			foreach ($result as $row) {
				$this->rows[$row[$this->primary]] = new SimpleRel_Row($row, $this->primary, $this->table, $this, $this->pdo, $this->structure);
			}
		}
	}
	
	/** Fetch next row of result
	* @return SimpleRel_Row or false if there is no row
	*/
	function fetch() {
		$this->execute();
		$return = current($this->rows);
		next($this->rows);
		return $return;
	}
	
	function getRows() {
		return $this->rows;
	}
	
	// IteratorAggregate implementation
	
	function getIterator() {
		$this->execute();
		return new ArrayIterator($this->rows);
	}
	
	// ArrayAccess implementation
	
	function offsetExists($key) {
		if ($this->single) {
			$clone = clone $this;
			$clone->where("$this->primary = ?", array($key));
			return $clone->count();
		} else {
			$this->execute();
			return isset($this->rows[$key]);
		}
	}
	
	function offsetGet($key) {
		if ($this->single) {
			$clone = clone $this;
			$clone->where("$this->primary = ?", array($key));
			return $clone->fetch();
		} else {
			$this->execute();
			return $this->rows[$key];
		}
	}
	
	function offsetSet($key, $value) {
		//! Exception
	}
	
	function offsetUnset($key) {
		$this->execute();
		unset($this->rows[$key]);
	}
	
}
