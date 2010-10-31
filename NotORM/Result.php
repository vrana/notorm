<?php

/** Filtered table representation
*/
class NotORM_Result extends NotORM_Abstract implements Iterator, ArrayAccess, Countable {
	protected $single;
	protected $select = array(), $conditions = array(), $where = array(), $parameters = array(), $order = array(), $limit = null, $offset = null, $group = "", $having = "";
	protected $data, $referencing = array(), $aggregation = array(), $accessed, $access;
	
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
	
	/** Save data to cache and empty result
	*/
	function __destruct() {
		if ($this->notORM->cache && !$this->select && isset($this->rows)) {
			$this->notORM->cache->save("$this->table;" . implode(",", $this->conditions), $this->access);
		}
		$this->rows = null;
	}
	
	protected function whereString() {
		$return = "";
		$driver = $this->notORM->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
		$where = $this->where;
		if (isset($this->limit) && $driver == "oci") {
			$where[] = ($this->offset ? "rownum > $this->offset AND " : "") . "rownum <= " . ($this->limit + $this->offset);
		}
		if ($where) {
			$return .= " WHERE (" . implode(") AND (", $where) . ")";
		}
		if ($this->group) {
			$return .= " GROUP BY $this->group";
		}
		if ($this->having) {
			$return .= " HAVING $this->having";
		}
		if ($this->order) {
			$return .= " ORDER BY " . implode(", ", $this->order);
		}
		if (isset($this->limit) && $driver != "oci" && $driver != "dblib") {
			$return .= " LIMIT $this->limit";
			if (isset($this->offset)) {
				$return .= " OFFSET $this->offset";
			}
		}
		return $return;
	}
	
	protected function topString() {
		if (isset($this->limit) && $this->notORM->connection->getAttribute(PDO::ATTR_DRIVER_NAME) == "dblib") {
			return " TOP ($this->limit)"; //! offset is not supported
		}
		return "";
	}
	
	/** Get SQL query
	* @return string
	*/
	function __toString() {
		$return = "SELECT" . $this->topString() . " ";
		if ($this->select) {
			$return .= implode(", ", $this->select);
		} elseif ($this->accessed) {
			$return .= "$this->table." . implode(", $this->table.", array_keys($this->accessed));
		} else {
			$return .= "$this->table.*";
		}
		$where = $this->whereString();
		$join = array();
		preg_match_all('~\\b(\\w+)\\.~i', implode(",", $this->select) . $where, $matches);
		foreach ($matches[1] as $name) {
			if ($name != $this->table) { // case-sensitive
				$table = $this->notORM->structure->getReferencedTable($name, $this->table);
				$column = $this->notORM->structure->getReferencedColumn($name, $this->table);
				$primary = $this->notORM->structure->getPrimary($table);
				$join[$name] = " LEFT JOIN $table AS $name ON $this->table.$column = $name.$primary";
			}
		}
		return "$return FROM $this->table" . implode($join) . $where;
	}
	
	protected function query($query) {
		if ($this->notORM->debug) {
			if (is_callable($this->notORM->debug)) {
				call_user_func($this->notORM->debug, $query, $this->parameters);
			} else {
				fwrite(STDERR, "-- $query;\n");
			}
		}
		$return = $this->notORM->connection->prepare($query);
		if (!$return->execute($this->parameters)) {
			return false;
		}
		return $return;
	}
	
	protected function quote($val) {
		return (!isset($val) ? "NULL"
			: ($val instanceof NotORM_Literal ? $val->value // SQL code - for example "NOW()"
			: $this->notORM->connection->quote($val)
		));
	}
	
	/** Insert row in a table
	* @param mixed array($column => $value)|Traversable for single row insert or NotORM_Result|string for INSERT ... SELECT
	* @return string auto increment value or false in case of an error
	*/
	function insert($data) {
		if ($this->notORM->freeze) {
			return false;
		}
		if ($data instanceof NotORM_Result) {
			$data = (string) $data;
		} elseif ($data instanceof Traversable) {
			$data = iterator_to_array($data);
		}
		if (is_array($data)) {
			//! driver specific empty $data
			$data = "(" . implode(", ", array_keys($data)) . ") VALUES (" . implode(", ", array_map(array($this, 'quote'), $data)) . ")";
		}
		// requiers empty $this->parameters
		if (!$this->query("INSERT INTO $this->table $data")) {
			return false;
		}
		return $this->notORM->connection->lastInsertId();
	}
	
	/** Update all rows in result set
	* @param array ($column => $value)
	* @return int number of affected rows or false in case of an error
	*/
	function update(array $data) {
		if ($this->notORM->freeze) {
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
		// joins in UPDATE are supported only in MySQL
		$return = $this->query("UPDATE" . $this->topString() . " $this->table SET " . implode(", ", $values) . $this->whereString());
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}
	
	/** Delete all rows in result set
	* @return int number of affected rows or false in case of an error
	*/
	function delete() {
		if ($this->notORM->freeze) {
			return false;
		}
		$return = $this->query("DELETE" . $this->topString() . " FROM $this->table" . $this->whereString());
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}
	
	/** Add select clause, more calls appends to the end
	* @param string for example "column, MD5(column) AS column_md5"
	* @return NotORM_Result fluent interface
	*/
	function select($columns) {
		$this->__destruct();
		$this->select[] = $columns;
		return $this;
	}
	
	/** Add where condition, more calls appends with AND
	* @param string condition possibly containing ? or :name
	* @param mixed array accepted by PDOStatement::execute or a scalar value
	* @param mixed ...
	* @return NotORM_Result fluent interface
	*/
	function where($condition, $parameters = array()) {
		$this->__destruct();
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
			$clone = clone $parameters;
			if (!$clone->select) {
				$clone->select = array($this->notORM->structure->getPrimary($clone->table));
			}
			if ($this->notORM->connection->getAttribute(PDO::ATTR_DRIVER_NAME) != "mysql") {
				$condition .= " IN ($clone)";
			} else {
				$in = array();
				foreach ($clone as $row) {
					$val = implode(", ", array_map(array($this, 'quote'), iterator_to_array($row)));
					$in[] = (count($row) == 1 ? "($val)" : $val);
				}
				$condition .= " IN (" . ($in ? implode(", ", $in) : "NULL") . ")";
			}
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
	
	/** Add order clause, more calls appends to the end
	* @param string for example "column1, column2 DESC"
	* @return NotORM_Result fluent interface
	*/
	function order($columns) {
		$this->rows = null;
		$this->order[] = $columns;
		return $this;
	}
	
	/** Set limit clause, more calls rewrite old values
	* @param int
	* @param int
	* @return NotORM_Result fluent interface
	*/
	function limit($limit, $offset = null) {
		$this->rows = null;
		$this->limit = $limit;
		$this->offset = $offset;
		return $this;
	}
	
	/** Set group clause, more calls rewrite old values
	* @param string
	* @param string
	* @return NotORM_Result fluent interface
	*/
	function group($columns, $having = "") {
		$this->__destruct();
		$this->group = $columns;
		$this->having = $having;
		return $this;
	}
	
	/** Execute aggregation function
	* @param string
	* @return string
	*/
	function aggregation($function) {
		$query = "SELECT $function FROM $this->table";
		if ($this->where) {
			$query .= " WHERE (" . implode(") AND (", $this->where) . ")";
		}
		foreach ($this->query($query)->fetch() as $val) {
			return $val;
		}
	}
	
	/** Count number of rows
	* @param string
	* @return int
	*/
	function count($column = "") {
		if (!$column) {
			$this->execute();
			return count($this->data);
		}
		return $this->aggregation("COUNT($column)");
	}
	
	/** Return minimum value from a column
	* @param string
	* @return int
	*/
	function min($column) {
		return $this->aggregation("MIN($column)");
	}
	
	/** Return maximum value from a column
	* @param string
	* @return int
	*/
	function max($column) {
		return $this->aggregation("MAX($column)");
	}
	
	/** Return sum of values in a column
	* @param string
	* @return int
	*/
	function sum($column) {
		return $this->aggregation("SUM($column)");
	}
	
	/** Execute built query
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
				$this->rows[$key] = new $this->notORM->rowClass($row, $this);
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
	
	/** Fetch all rows as associative array
	* @param string
	* @param string
	* @return array
	*/
	function fetchPairs($key, $value) {
		$return = array();
		// no $clone->select = array($key, $value) to allow efficient caching with repetitive calls with different parameters
		foreach ($this as $row) {
			$return[$row[$key]] = $row[$value];
		}
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
	
	/** @return NotORM_Row */
	function current() {
		return current($this->data);
	}
	
	/** @return string row ID */
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
	
	/** Test if row exists
	* @param string row ID
	* @return bool
	*/
	function offsetExists($key) {
		if ($this->single && !isset($this->data)) {
			$clone = clone $this;
			$clone->where($this->primary, $key);
			return $clone->count();
			// can also use array_pop($this->where) instead of clone to save memory
		} else {
			$this->execute();
			return isset($this->data[$key]);
		}
	}
	
	/** Get specified row
	* @param string row ID
	* @return NotORM_Row or null if there is no such row
	*/
	function offsetGet($key) {
		if ($this->single && !isset($this->data)) {
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
	
	/** Mimic row
	* @param string row ID
	* @param NotORM_Row
	* @return null
	*/
	function offsetSet($key, $value) {
		$this->execute();
		$this->data[$key] = $value;
	}
	
	/** Remove row from result set
	* @param string row ID
	* @return null
	*/
	function offsetUnset($key) {
		$this->execute();
		unset($this->data[$key]);
	}
	
}
