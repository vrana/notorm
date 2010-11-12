<?php

/** Filtered table representation
*/
class NotORM_Result extends NotORM_Abstract implements Iterator, ArrayAccess, Countable {
	protected $single;
	protected $select = array(), $conditions = array(), $where = array(), $parameters = array(), $order = array(), $limit = null, $offset = null, $group = "", $having = "", $lock = null;
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
			$this->notORM->cache->save("$this->table;" . implode(",", $this->conditions), $this->access);
		}
		$this->rows = null;
	}
	
	/** Get SQL query
	* @return string
	*/
	function __toString() {
		$join = array();
		foreach (array(
			"where" => implode(",", array_map('reset', $this->where)),
			"rest" => implode(",", $this->select) . ",$this->group,$this->having," . implode(",", $this->order)
		) as $key => $val) {
			preg_match_all('~\\b(\\w+)\\.(\\w+)(\\s+IS\\b|\\s*<=>)?~i', $val, $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$name = $match[1];
				if ($name != $this->table) { // case-sensitive
					$table = $this->notORM->structure->getReferencedTable($name, $this->table);
					$column = $this->notORM->structure->getReferencedColumn($name, $this->table);
					$primary = $this->notORM->structure->getPrimary($table);
					$join[$name] = " " . (!isset($join[$name]) && $key == "where" && !isset($match[3]) ? "INNER" : "LEFT") . " JOIN $table" . ($table != $name ? " AS $name" : "") . " ON $this->table.$column = $name.$primary";
				}
			}
		}
		if (!isset($this->rows) && $this->notORM->cache && !is_string($this->accessed)) {
			$this->accessed = $this->notORM->cache->load("$this->table;" . implode(",", $this->conditions));
			$this->access = $this->accessed;
		}
		$prefix = ($join ? "$this->table." : "");
		$columns = "$prefix*";
		if ($this->select) {
			$columns = implode(", ", $this->select);
		} elseif ($this->accessed) {
			$columns = $prefix . implode(", $prefix", array_keys($this->accessed));
		}
		return $this->notORM->storage->select($columns, $this->table . implode($join), $this->where, $this->group, $this->having, $this->order, $this->limit, $this->offset, $this->lock);
	}
	
	/** Insert row in a table
	* @param mixed array($column => $value)|Traversable for single row insert or NotORM_Result|string for INSERT ... SELECT
	* @return string assigned ID or false in case of an error
	*/
	function insert($data) {
		if ($this->notORM->freeze) {
			return false;
		}
		return $this->notORM->storage->insert($this->table, $data);
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
		return $this->notORM->storage->update($this->table, $data, $this->where, $this->parameters, $this->order, $this->limit, $this->offset); // HAVING is not supported
	}
	
	/** Delete all rows in result set
	* @return int number of affected rows or false in case of an error
	*/
	function delete() {
		if ($this->notORM->freeze) {
			return false;
		}
		return $this->notORM->storage->delete($this->table, $this->where, $this->parameters, $this->order, $this->limit, $this->offset);
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
		if (is_array($condition)) { // where(array("column1" => 1, "column2 > ?" => 2))
			foreach ($condition as $key => $val) {
				$this->where($key, $val);
			}
			return $this;
		}
		$this->__destruct();
		$this->conditions[] = $condition;
		$where = array($condition, $parameters);
		$args = func_num_args();
		if ($args != 2 || strpbrk($condition, "?:")) { // where("column < ? OR column > ?", array(1, 2))
			if ($args != 2 || !is_array($parameters)) { // where("column < ? OR column > ?", 1, 2)
				$parameters = func_get_args();
				array_shift($parameters);
			}
			$this->parameters = array_merge($this->parameters, $parameters);
			unset($where[1]);
		} elseif ($parameters instanceof NotORM_Result) { // where("column", $db->$table())
			$clone = clone $parameters;
			if (!$clone->select) {
				$clone->select = array($this->notORM->structure->getPrimary($clone->table));
			}
			$where[1] = $clone;
		}
		$this->where[] = $where;
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
	
	/** Acquire lock
	* @param bool true for write, false for read, null to disable
	* @return NotORM_Result fluent interface
	*/
	function lock($exclusive = true) {
		$this->lock = $exclusive;
		return $this;
	}
	
	/** Execute aggregation function
	* @param string
	* @return string
	*/
	function aggregation($function) {
		$query = $this->notORM->storage->select($function, $this->table, $this->where);
		foreach ($this->notORM->storage->query($query, $this->parameters) as $row) {
			foreach ($row as $val) {
				return $val;
			}
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
			$result = $this->notORM->storage->query($this->__toString(), $this->parameters);
			$this->rows = array();
			if ($result) {
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
