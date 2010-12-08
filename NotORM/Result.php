<?php

/**
 * Filtered table representation.
 */
class NotORM_Result extends NotORM_Abstract implements Iterator, ArrayAccess, Countable // not IteratorAggregate because $this->data can be changed during iteration
{
	protected $single;
	protected $select = array();
	protected $conditions = array();
	protected $where = array();
	protected $parameters = array();
	protected $order = array();
	protected $limit = NULL;
	protected $offset = NULL;
	protected $group = '';
	protected $having = '';

	protected $data;
	protected $referencing = array();
	protected $aggregation = array();
	protected $accessed;
	protected $access;
	protected $keys = array();



	/**
	 * Create table result.
	* @param string
	* @param NotORM
	* @param bool single row
	*/
	protected function __construct($table, NotORM $notORM, $single = FALSE)
	{
		$this->table = $table;
		$this->notORM = $notORM;
		$this->single = $single;
		$this->primary = $notORM->structure->getPrimary($table);
	}



	/**
	 * Save data to cache and empty result.
	*/
	function __destruct()
	{
		if ($this->notORM->cache && !$this->select && $this->rows !== NULL) {
			$access = $this->access;
			if (is_array($access)) {
				$access = array_filter($access);
			}
			$this->notORM->cache->save("$this->table;" . implode(",", $this->conditions), $access);
		}
		$this->rows = NULL;
	}



	protected function whereString()
	{
		$return = '';
		$driver = $this->notORM->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
		$where = $this->where;
		if ($this->limit !== NULL && $driver === 'oci') {
			$where[] = ($this->offset ? "rownum > $this->offset AND " : '') . 'rownum <= ' . ($this->limit + $this->offset);
		}
		if ($where) {
			$return .= ' WHERE (' . implode(') AND (', $where) . ')';
		}
		if ($this->group) {
			$return .= " GROUP BY $this->group";
		}
		if ($this->having) {
			$return .= " HAVING $this->having";
		}
		if ($this->order) {
			$return .= ' ORDER BY ' . implode(', ', $this->order);
		}
		if ($this->limit !== NULL && $driver !== 'oci' && $driver !== 'dblib') {
			$return .= " LIMIT $this->limit";
			if ($this->offset !== NULL) {
				$return .= " OFFSET $this->offset";
			}
		}
		return $return;
	}



	protected function topString()
	{
		if ($this->limit !== NULL && $this->notORM->connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'dblib') {
			return " TOP ($this->limit)"; //! offset is not supported
		}
		return '';
	}



	/**
	 * Get SQL query.
	* @return string
	*/
	function __toString()
	{
		$return = 'SELECT' . $this->topString() . ' ';
		$join = array();

		foreach (array(
			'where' => implode(',', $this->where),
			'rest' => implode(',', $this->select) . ",$this->group,$this->having," . implode(',', $this->order)
		) as $key => $val) {
			preg_match_all('~\\b(\\w+)\\.(\\w+)(\\s+IS\\b|\\s*<=>)?~i', $val, $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$name = $match[1];
				if ($name !== $this->table) { // case-sensitive
					$table = $this->notORM->structure->getReferencedTable($name, $this->table);
					$column = $this->notORM->structure->getReferencedColumn($name, $this->table);
					$primary = $this->notORM->structure->getPrimary($table);
					$join[$name] = ' ' . (!isset($join[$name]) && $key === 'where' && !isset($match[3]) ? 'INNER' : 'LEFT') . " JOIN $table" . ($table !== $name ? " AS $name" : '') . " ON $this->table.$column = $name.$primary";
				}
			}
		}

		if ($this->rows === NULL && $this->notORM->cache && !is_string($this->accessed)) {
			$this->accessed = $this->notORM->cache->load("$this->table;" . implode(',', $this->conditions));
			$this->access = $this->accessed;
		}

		if ($this->select) {
			$return .= implode(', ', $this->select);

		} elseif ($this->accessed) {
			$return .= ($join ? "$this->table." : '') . implode(', ' . ($join ? "$this->table." : ''), array_keys($this->accessed));

		} else {
			$return .= ($join ? "$this->table." : '') . '*';
		}

		return "$return FROM $this->table" . implode($join) . $this->whereString();
	}



	protected function query($query)
	{
		if (is_callable($this->notORM->debug)) {
			$start = microtime(TRUE);
		}
		$return = $this->notORM->connection->prepare($query);
		$result = $return->execute($this->parameters);
		if ($this->notORM->debug) {
			if (is_callable($this->notORM->debug)) {
				call_user_func($this->notORM->debug, $query, $this->parameters, microtime(TRUE) - $start);
			} else {
				fwrite(STDERR, "-- $query;\n");
			}
		}
		return ($result ? $return : FALSE);
	}



	protected function quote($val)
	{
		if ($val instanceof DateTime) {
			$val = $val->format('Y-m-d H:i:s'); //! may be driver specific
		}
		return ($val === NULL ? 'NULL'
			: ($val instanceof NotORM_Literal ? $val->value // SQL code - for example 'NOW()'
			: $this->notORM->connection->quote($val)
		));
	}



	/**
	 * Insert row in a table.
	* @param mixed array($column => $value)|Traversable for single row insert or NotORM_Result|string for INSERT ... SELECT
	 * @return NotORM_Row or FALSE in case of an error or number of affected rows for INSERT ... SELECT
	*/
	function insert($data)
	{
		if ($this->notORM->freeze) {
			return FALSE;
		}

		if ($data instanceof NotORM_Result) {
			$data = (string) $data;

		} elseif ($data instanceof Traversable) {
			$data = iterator_to_array($data);
		}

		$values = $data;
		if (is_array($data)) {
			//! driver specific empty $data
			$values = '(' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', array_map(array($this, 'quote'), $data)) . ')';
		}
		// requiers empty $this->parameters
		$return = $this->query("INSERT INTO $this->table $values");
		if (!$return) {
			return FALSE;
		}

		$this->rows = NULL;
		if (!is_array($data)) {
			return $return->rowCount();
		}

		if (!isset($data[$this->primary]) && ($id = $this->notORM->connection->lastInsertId())) {
			$data[$this->primary] = $id;
		}
		return new NotORM_Row($data, $this);
	}



	/**
	 * Update all rows in result set.
	* @param array ($column => $value)
	 * @return int number of affected rows or FALSE in case of an error
	*/
	function update(array $data)
	{
		if ($this->notORM->freeze) {
			return FALSE;
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
		$return = $this->query('UPDATE' . $this->topString() . " $this->table SET " . implode(', ', $values) . $this->whereString());
		if (!$return) {
			return FALSE;
		}
		return $return->rowCount();
	}



	/**
	 * Delete all rows in result set.
	 * @return int number of affected rows or FALSE in case of an error
	*/
	function delete()
	{
		if ($this->notORM->freeze) {
			return FALSE;
		}
		$return = $this->query('DELETE' . $this->topString() . " FROM $this->table" . $this->whereString());
		if (!$return) {
			return FALSE;
		}
		return $return->rowCount();
	}



	/**
	 * Add select clause, more calls appends to the end.
	* @param string for example "column, MD5(column) AS column_md5"
	* @return NotORM_Result fluent interface
	*/
	function select($columns)
	{
		$this->__destruct();
		$this->select[] = $columns;
		return $this;
	}



	/**
	 * Add where condition, more calls appends with AND.
	* @param string condition possibly containing ? or :name
	* @param mixed array accepted by PDOStatement::execute or a scalar value
	* @param mixed ...
	* @return NotORM_Result fluent interface
	*/
	function where($condition, $parameters = array())
	{
		if (is_array($condition)) { // where(array('column1' => 1, 'column2 > ?' => 2))
			foreach ($condition as $key => $val) {
				$this->where($key, $val);
			}
			return $this;
		}
		$this->__destruct();
		$this->conditions[] = $condition;
		$args = func_num_args();
		if ($args !== 2 || strpbrk($condition, '?:')) { // where('column < ? OR column > ?', array(1, 2))
			if ($args !== 2 || !is_array($parameters)) { // where('column < ? OR column > ?', 1, 2)
				$parameters = func_get_args();
				array_shift($parameters);
			}
			$this->parameters = array_merge($this->parameters, $parameters);

		} elseif ($parameters === NULL) { // where('column', NULL)
			$condition .= ' IS NULL';

		} elseif ($parameters instanceof NotORM_Result) { // where('column', $db->$table())
			$clone = clone $parameters;
			if (!$clone->select) {
				$clone->select = array($this->notORM->structure->getPrimary($clone->table));
			}
			if ($this->notORM->connection->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
				$condition .= " IN ($clone)";
			} else {
				$in = array();
				foreach ($clone as $row) {
					$val = implode(', ', array_map(array($this, 'quote'), iterator_to_array($row)));
					$in[] = (count($row) === 1 ? $val : "($val)");
				}
				$condition .= ' IN (' . ($in ? implode(', ', $in) : 'NULL') . ')';
			}

		} elseif (!is_array($parameters)) { // where('column', 'x')
			$condition .= ' = ' . $this->quote($parameters);

		} else { // where('column', array(1, 2))
			$in = 'NULL';
			if ($parameters) {
				$in = implode(', ', array_map(array($this, 'quote'), $parameters));
			}
			$condition .= " IN ($in)";
		}

		$this->where[] = $condition;
		return $this;
	}



	/**
	 * Add order clause, more calls appends to the end.
	 * @param  string for example 'column1, column2 DESC'
	* @return NotORM_Result fluent interface
	*/
	function order($columns)
	{
		$this->rows = NULL;
		$this->order[] = $columns;
		return $this;
	}



	/**
	 * Set limit clause, more calls rewrite old values.
	* @param int
	* @param int
	* @return NotORM_Result fluent interface
	*/
	function limit($limit, $offset = NULL)
	{
		$this->rows = NULL;
		$this->limit = $limit;
		$this->offset = $offset;
		return $this;
	}



	/**
	 * Set group clause, more calls rewrite old values.
	* @param string
	* @param string
	* @return NotORM_Result fluent interface
	*/
	function group($columns, $having = '')
	{
		$this->__destruct();
		$this->group = $columns;
		$this->having = $having;
		return $this;
	}



	/**
	 * Execute aggregation function.
	* @param string
	* @return string
	*/
	function aggregation($function)
	{
		$query = "SELECT $function FROM $this->table";
		if ($this->where) {
			$query .= ' WHERE (' . implode(') AND (', $this->where) . ')';
		}
		foreach ($this->query($query)->fetch() as $val) {
			return $val;
		}
	}



	/**
	 * Count number of rows.
	* @param string
	* @return int
	*/
	function count($column = '')
	{
		if (!$column) {
			$this->execute();
			return count($this->data);
		}
		return $this->aggregation("COUNT($column)");
	}



	/**
	 * Return minimum value from a column.
	* @param string
	* @return int
	*/
	function min($column)
	{
		return $this->aggregation("MIN($column)");
	}



	/**
	 * Return maximum value from a column.
	* @param string
	* @return int
	*/
	function max($column)
	{
		return $this->aggregation("MAX($column)");
	}



	/**
	 * Return sum of values in a column.
	* @param string
	* @return int
	*/
	function sum($column)
	{
		return $this->aggregation("SUM($column)");
	}



	/**
	 * Execute built query.
	 * @return NULL
	*/
	protected function execute()
	{
		if ($this->rows !== NULL) {
			return;
		}

		$result = FALSE;
		$exception = NULL;
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
					$this->access[$this->primary] = TRUE;
					}
				}
				$this->rows[$key] = new $this->notORM->rowClass($row, $this);
			}
		}
		$this->data = $this->rows;
	}



	/**
	 * Fetch next row of result.
	 * @return NotORM_Row or FALSE if there is no row
	*/
	function fetch()
	{
		$this->execute();
		$return = current($this->data);
		next($this->data);
		return $return;
	}



	/**
	 * Fetch all rows as associative array.
	* @param string
	* @param string column name used for an array value or an empty string for the whole row
	* @return array
	*/
	function fetchPairs($key, $value = '')
	{
		$return = array();
		// no $clone->select = array($key, $value) to allow efficient caching with repetitive calls with different parameters
		foreach ($this as $row) {
			$return[$row[$key]] = ($value !== '' ? $row[$value] : $row);
		}
		return $return;
	}



	protected function access($key, $delete = FALSE)
	{
		if ($delete) {
			if (is_array($this->access)) {
				$this->access[$key] = FALSE;
			}
			return FALSE;
		}

		if ($key === NULL) {
			$this->access = '';
		} elseif (!is_string($this->access)) {
			$this->access[$key] = TRUE;
		}

		if (!$this->select && $this->accessed && ($key === NULL || !isset($this->accessed[$key]))) {
			$this->accessed = '';
			$this->rows = NULL;
			return TRUE;
		}
		return FALSE;
	}



	/********************* interface Iterator ****************j*v**/



	function rewind()
	{
		$this->execute();
		$this->keys = array_keys($this->data);
		reset($this->keys);
	}



	/**
	 * @return NotORM_Row */
	function current()
	{
		return $this->data[current($this->keys)];
	}



	/**
	 * @return string row ID */
	function key()
	{
		return current($this->keys);
	}



	function next()
	{
		next($this->keys);
	}



	function valid()
	{
		return current($this->keys) !== FALSE;
	}



	/********************* interface ArrayAccess ****************j*v**/



	/**
	 * Test if row exists.
	* @param string row ID
	* @return bool
	*/
	function offsetExists($key)
	{
		if ($this->single && $this->data === NULL) {
			$clone = clone $this;
			$clone->where($this->primary, $key);
			return $clone->count();
			// can also use array_pop($this->where) instead of clone to save memory
		} else {
			$this->execute();
			return isset($this->data[$key]);
		}
	}



	/**
	 * Get specified row.
	* @param string row ID
	 * @return NotORM_Row or NULL if there is no such row
	*/
	function offsetGet($key)
	{
		if ($this->single && $this->data === NULL) {
			$clone = clone $this;
			$clone->where($this->primary, $key);
			$return = $clone->fetch();
			if (!$return) {
				return NULL;
			}
			return $return;

		} else {
			$this->execute();
			return $this->data[$key];
		}
	}



	/**
	 * Mimic row.
	* @param string row ID
	* @param NotORM_Row
	 * @return NULL
	*/
	function offsetSet($key, $value)
	{
		$this->execute();
		$this->data[$key] = $value;
	}



	/**
	 * Remove row from result set.
	* @param string row ID
	 * @return NULL
	*/
	function offsetUnset($key)
	{
		$this->execute();
		unset($this->data[$key]);
	}

}
