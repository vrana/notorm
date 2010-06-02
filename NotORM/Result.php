<?php

/** Filtered table representation
*/
class NotORM_Result extends NotORM_Abstract implements Iterator, ArrayAccess, Countable {
	protected $single;
	protected $select = array(), $conditions = array(), $where = array(), $parameters = array(), $order = array(), $limit = null, $offset = null;
	protected $data, $referencing = array(), $aggregation = array();
	
	/** Create table result
	* @param string
	* @param NotORM
	* @param bool single row
	*/
	protected function __construct($table, NotORM $notORM, $single = false) {
		$this->table = $table;
		$this->notORM = $notORM;
		$this->single = $single;
		$this->primary = $notORM->structure->getPrimary($table);
	}
	
	/** Get SQL query
	* @return string
	*/
	function __toString() {
		$return = "SELECT ";
		if ($this->select) {
			$return .= implode(", ", $this->select);
		} else {
			$return .= "*";
		}
		$return .= " FROM $this->table";
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
	
	protected function query($query) {
		//~ fwrite(STDERR, "$query;\n");
		$args = $this->parameters;
		array_unshift($args, $query);
		return call_user_func_array(array($this->notORM->connection, 'query'), $args);
	}
	
	protected function quote($string) {
		return $this->notORM->connection->getDriver()->escape($string, dibi::TEXT);
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
	* @param mixed accepted by DibiConnection::query
	* @param mixed ...
	* @return NotORM_Result fluent interface
	*/
	function where($condition, $parameters = null) {
		$this->conditions[] = $condition;
		$args = func_num_args();
		if ($args != 2 || strpbrk($condition, "%")) { // where("column = %s OR column = %s", 1, 2)
			$parameters = func_get_args();
			array_shift($parameters);
			$this->parameters = array_merge($this->parameters, $parameters);
		} elseif (is_null($parameters)) { // where("column", null)
			$condition .= " IS NULL";
		} elseif ($parameters instanceof NotORM_Result) { // where("column", $db->$table())
			$select = $parameters->select;
			$parameters->select = array($this->notORM->structure->getPrimary($parameters->table)); // can also use clone
			if (!in_array(strtolower($this->notORM->connection->getConfig("driver")), array("mysql", "mysqli"))) {
				$condition .= " IN ($parameters)";
			} else {
				$in = array();
				foreach ($parameters as $id => $row) {
					$in[] = $this->quote($id);
				}
				$condition .= " IN (" . ($in ? implode(", ", $in) : "NULL") . ")";
			}
			$parameters->select = $select;
		} elseif (!is_array($parameters)) { // where("column", "x")
			$condition .= " = " . $this->quote($parameters);
		} else { // where("column", array(1))
			$in = "NULL";
			if ($parameters) {
				$in = implode(", ", array_map(array($this, 'quote'), $parameters));
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
	* @return array numerical and string keys
	*/
	function group($functions, $having = "") {
		$query = "SELECT $functions FROM $this->table";
		if ($this->where) {
			$query .= " WHERE (" . implode(") AND (", $this->where) . ")";
		}
		if ($having != "") {
			$query .= " HAVING $having";
		}
		$return = $this->query($query)->fetch();
		if ($return) {
			$return = (array) $return + array_values((array) $return); // to allow list($min, $max)
		}
		return $return;
	}
	
	/** Execute built query
	* @param bool
	* @return null
	*/
	protected function execute() {
		if (!isset($this->rows)) {
			$result = $this->query($this->__toString());
			$this->rows = array();
			foreach ($result as $key => $row) {
				if (isset($row[$this->primary])) {
					$key = $row[$this->primary];
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
