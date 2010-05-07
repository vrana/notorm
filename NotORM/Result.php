<?php

/** Filtered table representation
*/
class NotORM_Result extends NotORM_Abstract implements Iterator, ArrayAccess, Countable {
	protected $single;
	protected $select = array(), $conditions = array(), $where = array(), $parameters = array(), $order = array(), $limit = null, $offset = null;
	protected $data, $referencing = array(), $aggregation = array(), $accessed, $access;
	
	/** Create table result
	* @param string
	* @param NotORM
	* @param bool single row
	*/
	function __construct($table, NotORM $notORM, $single = false) {
		$this->table = $table;
		$this->notORM = $notORM;
		$this->single = $single;
		$this->primary = $notORM->structure->getPrimary($table);
	}
	
	function __destruct() {
		if ($this->notORM->cache && !$this->select && isset($this->rows)) {
			$this->notORM->cache->save("$this->table;" . implode(",", $this->conditions), $this->access);
		}
	}
	
	protected function whereString() {
		$return = "";
		if ($this->where) {
			$return .= " WHERE (" . implode(") AND (", $this->where) . ")";
		}
		if ($this->order) {
			$return .= " ORDER BY " . implode(", ", $this->order);
		}
		if (isset($this->limit)) {
			$return .= " LIMIT $this->limit"; //! driver specific
			if (isset($this->offset)) {
				$return .= " OFFSET $this->offset";
			}
		}
		return $return;
	}
	
	/** Get SQL query
	* @return string
	*/
	function __toString() {
		$return = "SELECT ";
		if ($this->select) {
			$return .= implode(", ", $this->select);
		} elseif ($this->accessed) {
			$return .= implode(", ", array_keys($this->accessed));
		} else {
			$return .= "*";
		}
		return "$return FROM $this->table" . $this->whereString();
	}
	
	protected function query($query) {
		//~ fwrite(STDERR, "$query;\n");
		$return = $this->notORM->connection->prepare($query);
		if (!$return->execute($this->parameters)) {
			return false;
		}
		return $return;
	}
	
	protected function quote($val) {
		return (!isset($val) ? "NULL"
			: (is_array($val) ? implode("", $val) // SQL code - for example "NOW()"
			: $this->notORM->connection->quote($val)
		));
	}
	
	/** Disable persistence
	* @return NotORM_Result fluent interface
	*/
	function freeze() {
		$this->freeze = true;
		return $this;
	}
	
	/** Insert row in a table
	* @param array ($column => $value)
	* @return string auto increment value or false in case of an error
	*/
	function insert(array $data) {
		if ($this->freeze) {
			return false;
		}
		//! driver specific empty $data
		// requiers empty $this->parameters
		if (!$this->query("INSERT INTO $this->table (" . implode(", ", array_keys($data)) . ") VALUES (" . implode(", ", array_map(array($this, 'quote'), $data)) . ")")) {
			return false;
		}
		return $this->notORM->connection->lastInsertId();
	}
	
	/** Update all rows in result set
	* @param array ($column => $value)
	* @return int number of affected rows or false in case of an error
	*/
	function update(array $data) {
		if ($this->freeze) {
			return false;
		}
		if (!$data) {
			return 0;
		}
		$values = array();
		foreach ($data as $key => $val) {
			// doesn't use binding because $this->parameters can be filled by ? or :name
			$values[] = "$key = " . $this->quote($val);
		}
		$return = $this->query("UPDATE $this->table SET " . implode(", ", $values) . $this->whereString());
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}
	
	/** Delete all rows in result set
	* @return int number of affected rows or false in case of an error
	*/
	function delete() {
		if ($this->freeze) {
			return false;
		}
		$return = $this->query("DELETE FROM $this->table" . $this->whereString());
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}
	
	/** Set select clause, more calls appends to the end
	* @param string for example "column, MD5(column) AS column_md5"
	* @return NotORM_Result fluent interface
	*/
	function select($columns) {
		$this->select[] = $columns;
		return $this;
	}
	
	/** Set where condition, more calls appends with AND
	* @param string condition possibly containing ? or :name
	* @param mixed array accepted by PDOStatement::execute or a scalar value
	* @param mixed ...
	* @return NotORM_Result fluent interface
	*/
	function where($condition, $parameters = array()) {
		$this->conditions[] = $condition;
		$args = func_num_args();
		if ($args != 2 || strpbrk($condition, "?:")) { // where("column = ? OR column = ?", array(1, 2))
			if ($args != 2 || !is_array($parameters)) { // where("column = ?", 1)
				$parameters = func_get_args();
				array_shift($parameters);
			}
			$this->parameters = array_merge($this->parameters, $parameters);
		} elseif (is_null($parameters)) { // where("column", null)
			$condition .= " IS NULL";
		} elseif ($parameters instanceof NotORM_Result) { // where("column", $db->$table())
			$select = $parameters->select;
			if (!$select) {
				$parameters->select = array($this->notORM->structure->getPrimary($parameters->table)); // can also use clone
			}
			$condition .= " IN ($parameters)";
			$parameters->select = $select;
		} elseif (!is_array($parameters)) { // where("column", "x")
			$condition .= " = " . $this->notORM->connection->quote($parameters);
		} else { // where("column", array(1))
			$in = "NULL";
			if ($parameters) {
				$in = implode(", ", array_map(array($this->notORM->connection, 'quote'), $parameters));
			}
			$condition .= " IN ($in)";
		}
		$this->where[] = $condition;
		return $this;
	}
	
	/** Set order clause, more calls appends to the end
	* @param string for example "column1, column2 DESC"
	* @return NotORM_Result fluent interface
	*/
	function order($columns) {
		$this->order[] = $columns;
		return $this;
	}
	
	/** Set limit clause, more calls rewrite old values
	* @param int
	* @param int
	* @return NotORM_Result fluent interface
	*/
	function limit($limit, $offset = null) {
		$this->limit = $limit;
		$this->offset = $offset;
		return $this;
	}
	
	/** Count number of rows
	* @return int
	*/
	function count() {
		$this->execute();
		return count($this->data);
	}
	
	/** Execute aggregation functions
	* @param string for example "COUNT(*), MAX(id)"
	* @param string
	* @return array using PDO::FETCH_BOTH
	*/
	function group($functions, $having = "") {
		$query = "SELECT $functions FROM $this->table";
		if ($this->where) {
			$query .= " WHERE (" . implode(") AND (", $this->where) . ")";
		}
		if ($having != "") {
			$query .= " HAVING $having";
		}
		return $this->query($query)->fetch();
	}
	
	/** Execute built query
	* @param bool
	* @return null
	*/
	protected function execute() {
		if (!isset($this->rows)) {
			if ($this->notORM->cache && !is_string($this->accessed)) {
				$this->accessed = $this->notORM->cache->load("$this->table;" . implode(",", $this->conditions));
				$this->access = $this->accessed;
			}
			$result = $this->query($this->__toString());
			$result->setFetchMode(PDO::FETCH_ASSOC);
			$this->rows = array();
			foreach ($result as $key => $row) {
				if (isset($row[$this->primary])) {
					$key = $row[$this->primary];
					if (!is_string($this->access)) {
						$this->access[$this->primary] = true;
					}
				}
				$this->rows[$key] = new NotORM_Row($row, $this);
			}
			$this->data = $this->rows;
		}
	}
	
	/** Fetch next row of result
	* @return NotORM_Row or false if there is no row
	*/
	function fetch() {
		$this->execute();
		$return = current($this->data);
		next($this->data);
		return $return;
	}
	
	protected function access($key) {
		if (!isset($key)) {
			$this->access = '';
		} elseif (!is_string($this->access)) {
			$this->access[$key] = true;
		}
		if (!$this->select && $this->accessed && (!isset($key) || !isset($this->accessed[$key]))) {
			$this->accessed = '';
			$this->rows = null;
			return true;
		}
		return false;
	}
	
	// Iterator implementation (not IteratorAggregate because $this->data can be changed during iteration)
	
	function rewind() {
		$this->execute();
		reset($this->data);
	}
	
	function current() {
		return current($this->data);
	}
	
	function key() {
		return key($this->data);
	}
	
	function next() {
		next($this->data);
	}
	
	function valid() {
		return $this->current();
	}
	
	// ArrayAccess implementation
	
	function offsetExists($key) {
		if ($this->single) {
			$clone = clone $this;
			$clone->where($this->primary, $key);
			return $clone->count();
			// can also use array_pop($this->where) instead of clone to save memory
		} else {
			$this->execute();
			return isset($this->data[$key]);
		}
	}
	
	function offsetGet($key) {
		if ($this->single) {
			$clone = clone $this;
			$clone->where($this->primary, $key);
			$return = $clone->fetch();
			if (!$return) {
				return null;
			}
			return $return;
		} else {
			$this->execute();
			return $this->data[$key];
		}
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
