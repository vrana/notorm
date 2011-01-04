<?php

/** Filtered table representation
*/
class NotORM_Result extends NotORM_Abstract implements Iterator, ArrayAccess, Countable {
	protected $single;
	protected $select = array(), $conditions = array(), $where = array(), $parameters = array(), $order = array(), $limit = null, $offset = null, $group = "", $having = "";
	protected $data, $referencing = array(), $aggregation = array(), $accessed, $access, $keys = array();
	
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
			$access = $this->access;
			if (is_array($access)) {
				$access = array_filter($access);
			}
			$this->notORM->cache->save("$this->table;" . implode(",", $this->conditions), $access);
		}
		$this->rows = null;
	}
	
	protected function whereString() {
		$return = "";
		if ($this->group) {
			$return .= " GROUP BY $this->group";
		}
		if ($this->having) {
			$return .= " HAVING $this->having";
		}
		if ($this->order) {
			$return .= " ORDER BY " . implode(", ", $this->order);
		}
		$return = preg_replace('~\\b(\\w+\\.)+(\\w+\\.\\w)~', '\\2', $return); // rewrite tab1.tab2.col
		
		$driver = $this->notORM->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
		$where = $this->where;
		if (isset($this->limit) && $driver == "oci") {
			$where[] = ($this->offset ? "rownum > $this->offset AND " : "") . "rownum <= " . ($this->limit + $this->offset);
		}
		if ($where) {
			$return = " WHERE (" . implode(") AND (", $where) . ")$return";
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
	
	protected function createJoin($val, $inner = false) {
		$return = array();
		preg_match_all('~\\b([\\w.]+)\\.(\\w+)(\\s+IS\\b|\\s*<=>)?~i', $val, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$parent = $this->table;
			if ($match[1] != $parent) { // case-sensitive
				foreach (explode(".", $match[1]) as $name) {
					$table = $this->notORM->structure->getReferencedTable($name, $parent);
					$column = $this->notORM->structure->getReferencedColumn($name, $parent);
					$primary = $this->notORM->structure->getPrimary($table);
					$return[$name] = " " . (!isset($join[$name]) && $inner && !isset($match[3]) ? "INNER" : "LEFT") . " JOIN $table" . ($table != $name ? " AS $name" : "") . " ON $parent.$column = $name.$primary"; // should use alias if the table is used on more places
					$parent = $name;
				}
			}
		}
		return $return;
	}
	
	/** Get SQL query
	* @return string
	*/
	function __toString() {
		$return = "SELECT" . $this->topString() . " ";
		$join = $this->createJoin(implode(",", $this->conditions), true)
			+ $this->createJoin(implode(",", $this->select) . ",$this->group,$this->having," . implode(",", $this->order))
		;
		if (!isset($this->rows) && $this->notORM->cache && !is_string($this->accessed)) {
			$this->accessed = $this->notORM->cache->load("$this->table;" . implode(",", $this->conditions));
			$this->access = $this->accessed;
		}
		if ($this->select) {
			$return .= preg_replace('~\\b(\\w+\\.)+(\\w+\\.\\w)~', '\\2', implode(", ", $this->select));
		} elseif ($this->accessed) {
			$return .= ($join ? "$this->table." : "") . implode(", " . ($join ? "$this->table." : ""), array_keys($this->accessed));
		} else {
			$return .= ($join ? "$this->table." : "") . "*";
		}
		return "$return FROM $this->table" . implode($join) . $this->whereString();
	}
	
	protected function query($query) {
		if ($this->notORM->debug) {
			if (!is_callable($this->notORM->debug)) {
				fwrite(STDERR, "-- $query;\n");
			} elseif (call_user_func($this->notORM->debug, $query, $this->parameters) === false) {
				return false;
			}
		}
		$return = $this->notORM->connection->prepare($query);
		if (!$return->execute($this->parameters)) {
			return false;
		}
		return $return;
	}
	
	protected function quote($val) {
		if ($val instanceof DateTime) {
			$val = $val->format("Y-m-d H:i:s"); //! may be driver specific
		}
		return (!isset($val) ? "NULL"
			: ($val instanceof NotORM_Literal ? $val->value // SQL code - for example "NOW()"
			: $this->notORM->connection->quote($val)
		));
	}
	
	/** Insert row in a table
	* @param mixed array($column => $value)|Traversable for single row insert or NotORM_Result|string for INSERT ... SELECT
	* @param ... used for extended insert
	* @return NotORM_Row inserted row or false in case of an error or number of affected rows for INSERT ... SELECT
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
		$insert = $data;
		if (is_array($data)) {
			$values = array();
			foreach (func_get_args() as $val) {
				if ($val instanceof Traversable) {
					$val = iterator_to_array($val);
				}
				$values[] = "(" . implode(", ", array_map(array($this, 'quote'), $val)) . ")";
			}
			//! driver specific empty $data and extended insert
			$insert = "(" . implode(", ", array_keys($data)) . ") VALUES " . implode(", ", $values);
		}
		// requires empty $this->parameters
		$return = $this->query("INSERT INTO $this->table $insert");
		if (!$return) {
			return false;
		}
		$this->rows = null;
		if (!is_array($data)) {
			return $return->rowCount();
		}
		if (!isset($data[$this->primary]) && ($id = $this->notORM->connection->lastInsertId())) {
			$data[$this->primary] = $id;
		}
		return new NotORM_Row($data, $this);
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
	* @param string ...
	* @return NotORM_Result fluent interface
	*/
	function select($columns) {
		$this->__destruct();
		foreach (func_get_args() as $columns) {
			$this->select[] = $columns;
		}
		return $this;
	}
	
	/** Add where condition, more calls appends with AND
	* @param string condition possibly containing ? or :name
	* @param mixed array accepted by PDOStatement::execute or a scalar value
	* @param mixed ...
	* @return NotORM_Result fluent interface
	*/
	function where($condition, $parameters = array()) {
		if (is_array($condition)) { // where(array("column1" => 1, "column2 > ?" => 2))
			foreach ($condition as $key => $val) {
				$this->where($key, $val);
			}
			return $this;
		}
		$this->__destruct();
		$this->conditions[] = $condition;
		$condition = preg_replace('~\\b(\\w+\\.)+(\\w+\\.\\w)~', '\\2', $condition);
		$args = func_num_args();
		if ($args != 2 || strpbrk($condition, "?:")) { // where("column < ? OR column > ?", array(1, 2))
			if ($args != 2 || !is_array($parameters)) { // where("column < ? OR column > ?", 1, 2)
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
					$in[] = (count($row) == 1 ? $val : "($val)");
				}
				$condition .= " IN (" . ($in ? implode(", ", $in) : "NULL") . ")";
			}
		} elseif (!is_array($parameters)) { // where("column", "x")
			$condition .= " = " . $this->quote($parameters);
		} else { // where("column", array(1, 2))
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
	* @param string ...
	* @return NotORM_Result fluent interface
	*/
	function order($columns) {
		$this->rows = null;
		foreach (func_get_args() as $columns) {
			$this->order[] = $columns;
		}
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
		$join = $this->createJoin(implode(",", $this->conditions), true) + $this->createJoin($function);
		$query = "SELECT $function FROM $this->table" . implode($join);
		if ($this->where) {
			$query .= " WHERE (" . implode(") AND (", $this->where) . ")";
		}
		foreach ($this->query($query)->fetch() as $return) {
			return $return;
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
			$result = false;
			$exception = null;
			try {
				$result = $this->query($this->__toString());
			} catch (PDOException $exception) {
				// handled later
			}
			if (!$result) {
				if (!$this->select && $this->accessed) {
					$this->accessed = '';
					$this->access = array();
					$result = $this->query($this->__toString());
				} elseif ($exception) {
					throw $exception;
				}
			}
			$this->rows = array();
			if ($result) {
				$result->setFetchMode(PDO::FETCH_ASSOC);
				foreach ($result as $key => $row) {
					if (isset($row[$this->primary])) {
						$key = $row[$this->primary];
						if (!is_string($this->access)) {
							$this->access[$this->primary] = true;
						}
					}
					$this->rows[$key] = new $this->notORM->rowClass($row, $this);
				}
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
	* @param string column name used for an array value or an empty string for the whole row
	* @return array
	*/
	function fetchPairs($key, $value = '') {
		$return = array();
		// no $clone->select = array($key, $value) to allow efficient caching with repetitive calls with different parameters
		foreach ($this as $row) {
			$return[$row[$key]] = ($value != '' ? $row[$value] : $row);
		}
		return $return;
	}
	
	protected function access($key, $delete = false) {
		if ($delete) {
			if (is_array($this->access)) {
				$this->access[$key] = false;
			}
			return false;
		}
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
		$this->keys = array_keys($this->data);
		reset($this->keys);
	}
	
	/** @return NotORM_Row */
	function current() {
		return $this->data[current($this->keys)];
	}
	
	/** @return string row ID */
	function key() {
		return current($this->keys);
	}
	
	function next() {
		next($this->keys);
	}
	
	function valid() {
		return current($this->keys) !== false;
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
