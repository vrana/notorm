<?php

/** Filtered table representation
*/
class NotORM_Result implements IteratorAggregate, ArrayAccess, Countable {
	protected $table, $connection, $structure, $primary, $single;
	protected $select = array(), $where = array(), $parameters = array(), $order = array(), $limit = null, $offset = null;
	protected $rows, $data, $referencing = array(), $aggregation = array();
	
	/** @internal used by NotORM_Row */
	public $referenced = array();
	
	/** Create table result
	* @param string
	* @param PDO|DibiConnection
	* @param NotORM_Structure
	* @param bool single row
	*/
	function __construct($table, $connection, NotORM_Structure $structure, $single = false) {
		$this->table = $table;
		$this->connection = $connection;
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
			$return .= implode(", ", $this->select);
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
		if (isset($this->limit)) {
			$return .= " LIMIT $this->limit"; //! driver specific
			if (isset($this->offset)) {
				$return .= " OFFSET $this->offset";
			}
		}
		return $return;
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
		$args = func_num_args();
		if ($args != 2 || strpbrk($condition, "?:%")) { // where("column = ? OR column = ?", array(1, 2))
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
				$parameters->select = array($this->structure->getPrimary($parameters->table)); // can also use clone
			}
			$condition .= " IN ($parameters)";
			$parameters->select = $select;
		} elseif (!is_array($parameters)) { // where("column", 'x')
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
	
	//! group by, having
	
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
	* @return array with numerical and string keys
	*/
	function aggregation($function) {
		$query = "SELECT $function FROM $this->table";
		if ($this->where) {
			$query .= " WHERE " . implode(" AND ", $this->where);
		}
		$return = $this->query($query)->fetch();
		if ($this->connection instanceof DibiConnection && $return) {
			$return = (array) $return + array_values((array) $return); // to allow list($min, $max)
		}
		return $return;
	}
	
	protected function query($query) {
		//~ fwrite(STDERR, "$query\n");
		if ($this->connection instanceof DibiConnection) {
			return $this->connection->query($query, $this->parameters);
		}
		$return = $this->connection->prepare($query);
		$return->execute($this->parameters);
		return $return;
	}
	
	protected function quote($string) {
		if ($this->connection instanceof DibiConnection) {
			return $this->connection->getDriver()->escape($string, dibi::TEXT);
		}
		return $this->connection->quote($string);
	}
	
	protected function execute() {
		if (!isset($this->rows)) {
			$result = $this->query($this->__toString());
			if ($this->connection instanceof PDO) {
				$result->setFetchMode(PDO::FETCH_ASSOC);
			}
			$this->rows = array();
			foreach ($result as $key => $row) {
				if (isset($row[$this->primary])) {
					$key = $row[$this->primary];
				}
				$this->rows[$key] = new NotORM_Row($row, $this->primary, $this->table, $this, $this->connection, $this->structure);
			}
			$this->data = $this->rows;
		}
	}
	
	function getRows() {
		return $this->rows;
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
	
	// IteratorAggregate implementation
	
	function getIterator() {
		$this->execute();
		return new ArrayIterator($this->data);
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
