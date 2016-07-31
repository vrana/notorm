<?php
namespace NotORM;

/**
 * Filtered table representation
 * @method Result and(mixed $condition, mixed $parameters = array()) Add AND condition
 * @method Result or(mixed $condition, mixed $parameters = array()) Add OR condition
 */
class Result extends AbstractClass implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable {
	protected $single;
	protected $select = array(), $conditions = array(), $where = array(), $parameters = array(), $order = array(), $limit = null, $offset = null, $group = "", $having = "", $lock = null;
	protected $union = array(), $unionOrder = array(), $unionLimit = null, $unionOffset = null;
	protected $data, $referencing = array(), $aggregation = array(), $accessed, $access, $keys = array();

	/**
	 * Create table result
	 * @param $table
	 * @param AbstractClass|Instance $notORM
	 * @param bool $single single row
	 * @access protected must be public because it is called from NotORM
	 */
	public function __construct($table, AbstractClass $notORM, $single = false) {
		$this->table = $table;
		$this->notORM = $notORM;
		$this->single = $single;
		$this->primary = $notORM->structure->getPrimary($table);
	}
	
	/**
	 * Save data to cache and empty result
	*/
	public function __destruct() {
		if ($this->notORM->cache && empty($this->select) && isset($this->rows)) {
			$access = $this->access;
			if (is_array($access)) {
				$access = array_filter($access);
			}
			$this->notORM->cache->save("$this->table;" . implode(",", $this->conditions), $access);
		}
		$this->rows = null;
		unset($this->data);
	}

	/**
	 * Limit for supported drivers
	 * @param int $limit
	 * @param int|null $offset
	 * @return string
	 */
	protected function limitString($limit, $offset = null) {
		$return = "";
		if (isset($limit) && $this->notORM->driver != "oci" && $this->notORM->driver != "dblib" && $this->notORM->driver != "mssql" && $this->notORM->driver != "sqlsrv") {
			$return .= " LIMIT $limit";
			if ($offset) {
				$return .= " OFFSET $offset";
			}
		}
		return $return;
	}

	/**
	 * Dot name notation support
	 * @param string $expression
	 * @return string
	 */
	protected function removeExtraDots($expression) {
		return preg_replace('~(?:\\b[a-z_][a-z0-9_.:]*[.:])?([a-z_][a-z0-9_]*)[.:]([a-z_*])~i', '\\1.\\2', $expression); // rewrite tab1.tab2.col
	}

	/**
	 * Where String
	 * @return string
	 */
	protected function whereString() {
		$return = "";
		if ($this->group) {
			$return .= " GROUP BY $this->group";
		}
		if ($this->having) {
			$return .= " HAVING $this->having";
		}
		if (!empty($this->order)) {
			$return .= " ORDER BY " . implode(", ", $this->order);
		}
		$return = $this->removeExtraDots($return);
		
		$where = $this->where;
		if (isset($this->limit) && $this->notORM->driver == "oci") {
			$where[] = ($where ? " AND " : "") . "(" . ($this->offset ? "rownum > $this->offset AND " : "") . "rownum <= " . ($this->limit + $this->offset) . ")"; //! rownum > doesn't work - requires subselect (see adminer/drivers/oracle.inc.php)
		}
		if (!empty($where)) {
			$return = " WHERE " . implode($where) . $return;
		}
		
		$return .= $this->limitString($this->limit, $this->offset);
		if (isset($this->lock)) {
			$return .= ($this->lock ? " FOR UPDATE" : " LOCK IN SHARE MODE");
		}
		return $return;
	}

	/**
	 * MSSQL limit support
	 * @param int $limit
	 * @return string
	 */
	protected function topString($limit) {
		if (isset($limit) && ($this->notORM->driver == "dblib" || $this->notORM->driver == "mssql" || $this->notORM->driver == "sqlsrv")) {
			return " TOP ($this->limit)"; //! offset is not supported
		}
		return "";
	}

	/**
	 * Joins
	 * @param string $val
	 * @return array
	 */
	protected function createJoins($val) {
		$return = array();
		preg_match_all('~\\b([a-z_][a-z0-9_.:]*[.:])[a-z_*]~i', $val, $matches);
		foreach ($matches[1] as $names) {
			$parent = $this->table;
			if ($names != "$parent.") { // case-sensitive
				preg_match_all('~\\b([a-z_][a-z0-9_]*)([.:])~i', $names, $matches, PREG_SET_ORDER);
				foreach ($matches as $match) {
					list(, $name, $delimiter) = $match;
					$table = $this->notORM->structure->getReferencedTable($name, $parent);
					$column = ($delimiter == ':' ? $this->notORM->structure->getPrimary($parent) : $this->notORM->structure->getReferencedColumn($name, $parent));
					$primary = ($delimiter == ':' ? $this->notORM->structure->getReferencedColumn($parent, $table) : $this->notORM->structure->getPrimary($table));
					$return[$name] = " LEFT JOIN $table" . ($table != $name ? " AS $name" : "") . " ON $parent.$column = $name.$primary"; // should use alias if the table is used on more places
					$parent = $name;
				}
			}
		}
		return $return;
	}
	
	/**
	 * Get SQL query
	 * @return string
	 */
	public function __toString() {
		$return = "SELECT" . $this->topString($this->limit, $this->offset) . " ";
		$join = $this->createJoins(implode(",", $this->conditions) . "," . implode(",", $this->select) . ",$this->group,$this->having," . implode(",", $this->order));
		if (!isset($this->rows) && $this->notORM->cache && !is_string($this->accessed)) {
			$this->accessed = $this->notORM->cache->load("$this->table;" . implode(",", $this->conditions));
			$this->access = $this->accessed;
		}
		if (!empty($this->select)) {
			$return .= $this->removeExtraDots(implode(", ", $this->select));
		} elseif ($this->accessed) {
			$return .= ($join ? "$this->table." : "") . implode(", " . ($join ? "$this->table." : ""), array_keys($this->accessed));
		} else {
			$return .= ($join ? "$this->table." : "") . "*";
		}
		$return .= " FROM $this->table" . implode($join) . $this->whereString();
		if (!empty($this->union)) {
			$return = ($this->notORM->driver == "sqlite" || $this->notORM->driver == "oci" ? $return : "($return)") . implode($this->union);
			if ($this->unionOrder) {
				$return .= " ORDER BY " . implode(", ", $this->unionOrder);
			}
			$return .= $this->limitString($this->unionLimit, $this->unionOffset);
			$top = $this->topString($this->unionLimit, $this->unionOffset);
			if ($top) {
				$return = "SELECT$top * FROM ($return) t";
			}
		}
		return $return;
	}

	/**
	 * Custom query build
	 * @param mixed $query
	 * @param $parameters
	 * @return bool
	 */
	protected function query($query, $parameters) {
		if ($this->notORM->debug) {
			if (!is_callable($this->notORM->debug)) {
				$debug = "$query;";
				if ($parameters) {
					$debug .= " -- " . implode(", ", array_map(array($this, 'quote'), $parameters));
				}
				$pattern = '(^' . preg_quote(dirname(__FILE__)) . '(\\.php$|[/\\\\]))'; // can be static
				$backtrace = null;
				foreach (debug_backtrace() as $backtrace) {
					if (isset($backtrace["file"]) && !preg_match($pattern, $backtrace["file"])) { // stop on first file outside NotORM source codes
						break;
					}
				}
				if (!is_null($backtrace)) {
					error_log("$backtrace[file]:$backtrace[line]:$debug\n", 0);
				}
			} elseif (call_user_func($this->notORM->debug, $query, $parameters) === false) {
				return false;
			}
		}
		$return = $this->notORM->connection->prepare($query);
		if (!$return || !$return->execute(array_map(array($this, 'formatValue'), $parameters))) {
			$return = false;
		}
		if ($this->notORM->debugTimer) {
			call_user_func($this->notORM->debugTimer);
		}
		return $return;
	}

	/**
	 * Format values
	 * @param mixed $val
	 * @return string
	 */
	protected function formatValue($val) {
		if ($val instanceof \DateTime) {
			return $val->format("Y-m-d H:i:s"); //! may be driver specific
		}
		return $val;
	}

	/**
	 * Quote values
	 * @param mixed $val
	 * @return string
	 */
	protected function quote($val) {
		if (!isset($val)) {
			return "NULL";
		}
		if (is_array($val)) { // (a, b) IN ((1, 2), (3, 4))
			return "(" . implode(", ", array_map(array($this, 'quote'), $val)) . ")";
		}
		$val = $this->formatValue($val);
		if (is_float($val)) {
			return sprintf("%F", $val); // otherwise depends on setlocale()
		}
		if ($val === false) {
			return "0";
		}
		if (is_int($val) || $val instanceof Literal) { // number or SQL code - for example "NOW()"
			return (string) $val;
		}
		return $this->notORM->connection->quote($val);
	}
	
	/**
	 * Shortcut for call_user_func_array(array($this, 'insert'), $rows)
	 * @param array $rows
	 * @return int number of affected rows or false in case of an error
	 */
	public function insert_multi(array $rows) {
		if ($this->notORM->freeze) {
			return false;
		}
		if (empty($rows)) {
			return 0;
		}
		$data = reset($rows);
		$parameters = array();
		if ($data instanceof Result) {
			$parameters = $data->parameters; //! other parameters
			$data = (string) $data;
		} elseif ($data instanceof \Traversable) {
			$data = iterator_to_array($data);
		}
		$insert = $data;
		if (is_array($data)) {
			$values = array();
			foreach ($rows as $value) {
				if ($value instanceof \Traversable) {
					$value = iterator_to_array($value);
				}
				$values[] = $this->quote($value);
				foreach ($value as $val) {
					if ($val instanceof Literal && !empty($val->parameters)) {
						$parameters = array_merge($parameters, $val->parameters);
					}
				}
			}
			//! driver specific extended insert
			$insert = (!empty($data) || $this->notORM->driver == "mysql"
				? "(" . implode(", ", array_keys($data)) . ") VALUES " . implode(", ", $values)
				: "DEFAULT VALUES"
			);
		}
		// requires empty $this->parameters
		$return = $this->query("INSERT INTO $this->table $insert", $parameters);
		if (!$return) {
			return false;
		}
		$this->rows = null;
		return $return->rowCount();
	}

	/**
	 * Insert row in a table
	 * array($column => $value)|Traversable for single row insert or Result|string for INSERT ... SELECT
	 * @param string $data used for extended insert
	 * @return mixed inserted NotORM\Row or false in case of an error or number of affected rows for INSERT ... SELECT
	 */
	public function insert($data) {
		$rows = func_get_args();
		$return = $this->insert_multi($rows);
		if (!$return) {
			return false;
		}
		if (!is_array($data)) {
			return $return;
		}
		if (!isset($data[$this->primary]) && ($id = $this->notORM->connection->lastInsertId($this->notORM->structure->getSequence($this->table)))) {
			$data[$this->primary] = $id;
		}
		return new $this->notORM->rowClass($data, $this);
	}
	
	/**
	 * Update all rows in result set
	 * @param array ($column => $value)
	 * @return int number of affected rows or false in case of an error
	 */
	public function update(array $data) {
		if ($this->notORM->freeze) {
			return false;
		}
		if (empty($data)) {
			return 0;
		}
		$values = array();
		$parameters = array();
		foreach ($data as $key => $val) {
			// doesn't use binding because $this->parameters can be filled by ? or :name
			$values[] = "$key = " . $this->quote($val);
			if ($val instanceof Literal && !empty($val->parameters)) {
				$parameters = array_merge($parameters, $val->parameters);
			}
		}
		if (!empty($this->parameters)) {
			$parameters = array_merge($parameters, $this->parameters);
		}
		// joins in UPDATE are supported only in MySQL
		$return = $this->query("UPDATE" . $this->topString($this->limit, $this->offset) . " $this->table SET " . implode(", ", $values) . $this->whereString(), $parameters);
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}
	
	/**
	 * Insert row or update if it already exists
	 * @param array $unique ($column => $value)
	 * @param array $insert ($column => $value)
	 * @param array $update ($column => $value), empty array means use $insert
	 * @return int number of affected rows or false in case of an error
	 */
	public function insert_update(array $unique, array $insert, array $update = array()) {
		if (empty($update)) {
			$update = $insert;
		}
		$insert = $unique + $insert;
		$values = "(" . implode(", ", array_keys($insert)) . ") VALUES " . $this->quote($insert);
		//! parameters
		if ($this->notORM->driver == "mysql") {
			$set = array();
			if (empty($update)) {
				$update = $unique;
			}
			foreach ($update as $key => $val) {
				$set[] = "$key = " . $this->quote($val);
				//! parameters
			}
			return $this->insert("$values ON DUPLICATE KEY UPDATE " . implode(", ", $set));
		} else {
			$connection = $this->notORM->connection;
			$errorMode = $connection->getAttribute(\PDO::ATTR_ERRMODE);
			$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			try {
				$return = $this->insert($values);
				$connection->setAttribute(\PDO::ATTR_ERRMODE, $errorMode);
				return $return;
			} catch (\PDOException $e) {
				$connection->setAttribute(\PDO::ATTR_ERRMODE, $errorMode);
				if ($e->getCode() == "23000" || $e->getCode() == "23505") { // "23000" - duplicate key, "23505" unique constraint pgsql
					if (empty($update)) {
						return 0;
					}
					$clone = clone $this;
					$return = $clone->where($unique)->update($update);
					return ($return ? $return + 1 : $return);
				}
				if ($errorMode == \PDO::ERRMODE_EXCEPTION) {
					throw $e;
				} elseif ($errorMode == \PDO::ERRMODE_WARNING) {
					trigger_error("\PDOStatement::execute(): " . $e->getMessage(), E_USER_WARNING); // E_WARNING is unusable
				}
			}
		}
	}
	
	/**
	 * Get last insert ID
	 * @return string number
	 */
	public function insert_id() {
		return $this->notORM->connection->lastInsertId();
	}
	
	/**
	 * Delete all rows in result set
	 * @return int number of affected rows or false in case of an error
	 */
	public function delete() {
		if ($this->notORM->freeze) {
			return false;
		}
		$return = $this->query("DELETE" . $this->topString($this->limit, $this->offset) . " FROM $this->table" . $this->whereString(), $this->parameters);
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}
	
	/**
	 * Add select clause, more calls appends to the end
	 * @param string $columns for example "column, MD5(column) AS column_md5", empty string to reset previously set columns
	 * @return Result fluent interface
	 */
	public function select($columns) {
		$this->__destruct();
		if ($columns != "") {
			foreach (func_get_args() as $columns) {
				$this->select[] = $columns;
			}
		} else {
			$this->select = array();
		}
		return $this;
	}

	/**
	 * Add where condition, more calls appends with AND
	 * @param mixed $condition string possibly containing ? or :name; or array($condition => $parameters, ...)
	 * @param mixed|array $parameters accepted by \PDOStatement::execute or a scalar value
	 * @return Result fluent interface
	 */
	public function where($condition, $parameters = array()) {
		$args = func_get_args();
		return $this->whereOperator("AND", $args);
	}

	/**
	 * Where Operator
	 * @param string $operator
	 * @param array $args
	 * @return Result $this
	 */
	protected function whereOperator($operator, array $args) {
		$condition = $args[0];
		$parameters = (count($args) > 1 ? $args[1] : array());
		if (is_array($condition)) { // where(array("column1" => 1, "column2 > ?" => 2))
			foreach ($condition as $key => $val) {
				$this->where($key, $val);
			}
			return $this;
		}
		$this->__destruct();
		$this->conditions[] = "$operator $condition";
		$condition = $this->removeExtraDots($condition);
		if (count($args) != 2 || strpbrk($condition, "?:")) { // where("column < ? OR column > ?", array(1, 2))
			if (count($args) != 2 || !is_array($parameters)) { // where("column < ? OR column > ?", 1, 2)
				$parameters = array_slice($args, 1);
			}
			$this->parameters = array_merge($this->parameters, $parameters);
		} elseif ($parameters === null) { // where("column", null)
			$condition .= " IS NULL";
		} elseif ($parameters instanceof Result) { // where("column", $db->$table())
			$clone = clone $parameters;
			if (empty($clone->select)) {
				$clone->select($this->notORM->structure->getPrimary($clone->table));
			}
			if ($this->notORM->driver != "mysql") {
				if ($clone instanceof MultiResult) {
					array_shift($clone->select);
					$clone->single();
				}
				$condition .= " IN ($clone)";
				$this->parameters = array_merge($this->parameters, $clone->parameters);
			} else {
				$in = array();
				foreach ($clone as $row) {
					$row = array_values(iterator_to_array($row));
					if ($clone instanceof MultiResult && count($row) > 1) {
						array_shift($row);
					}
					if (count($row) == 1) {
						$in[] = $this->quote($row[0]);
					} else {
						$in[] = $this->quote($row);
					}
				}
				if (!empty($in)) {
					$condition .= " IN (" . implode(", ", $in) . ")";
				} else {
					$condition = "($condition) IS NOT NULL AND $condition IS NULL"; // $condition = "NOT id"
				}
			}
		} elseif (!is_array($parameters)) { // where("column", "x")
			$condition .= " = " . $this->quote($parameters);
		} else { // where("column", array(1, 2))
			$condition = $this->whereIn($condition, $parameters);
		}
		$this->where[] = (preg_match('~^\)+$~', $condition)
			? $condition
			: ($this->where ? " $operator " : "") . "($condition)"
		);
		return $this;
	}

	/**
	 * Where In
	 * @param $condition
	 * @param $parameters
	 * @return string
	 */
	protected function whereIn($condition, $parameters) {
		if (!$parameters) {
			$condition = "($condition) IS NOT NULL AND $condition IS NULL";
		} elseif ($this->notORM->driver != "oci") {
			$column = $condition;
			$condition .= " IN " . $this->quote($parameters);
			$nulls = array_filter($parameters, 'is_null');
			if (!empty($nulls)) {
				$condition = "$condition OR $column IS NULL";
			}
		} else { // http://download.oracle.com/docs/cd/B19306_01/server.102/b14200/expressions014.htm
			$or = array();
			for ($i=0, $c=count($parameters); $i<$c; $i += 1000) {
				$or[] = "$condition IN " . $this->quote(array_slice($parameters, $i, 1000));
			}
			$condition = implode(" OR ", $or);
		}
		return $condition;
	}

	/**
	 * Shortcut to where operator
	 * @param $name
	 * @param array $args
	 * @return Result
	 */
	public function __call($name, array $args) {
		$operator = strtoupper($name);
		switch ($operator) {
			case "AND":
			case "OR":
				return $this->whereOperator($operator, $args);
			default: trigger_error("Call to undefined method Result::$name()", E_USER_ERROR);
		}
	}

	/**
	 * Shortcut for where()
	 * @param $where
	 * @param array $parameters
	 * @return Result fluent interface
	 */
	public function __invoke($where, $parameters = array()) {
		$args = func_get_args();
		return $this->whereOperator("AND", $args);
	}
	
	/**
	 * Add order clause, more calls appends to the end
	 * @param $columns string "column1, column2 DESC" or array("column1", "column2 DESC"), empty string to reset previous order
	 * @return Result fluent interface
	 */
	public function order($columns) {
		$this->rows = null;
		if ($columns != "") {
			$columns = (is_array($columns) ? $columns : func_get_args());
			foreach ($columns as $column) {
				if (!empty($this->union)) {
					$this->unionOrder[] = $column;
				} else {
					$this->order[] = $column;
				}
			}
		} elseif (!empty($this->union)) {
			$this->unionOrder = array();
		} else {
			$this->order = array();
		}
		return $this;
	}

	/**
	 * Set limit clause, more calls rewrite old values
	 * @param int $limit
	 * @param int|null $offset
	 * @return Result fluent interface
	 */
	public function limit($limit, $offset = null) {
		$this->rows = null;
		if (!empty($this->union)) {
			$this->unionLimit = +$limit;
			$this->unionOffset = +$offset;
		} else {
			$this->limit = +$limit;
			$this->offset = +$offset;
		}
		return $this;
	}

	/**
	 * Set group clause, more calls rewrite old values
	 * @param string $columns
	 * @param string $having
	 * @return Result fluent interface
	 */
	public function group($columns, $having = "") {
		$this->__destruct();
		$this->group = $columns;
		$this->having = $having;
		return $this;
	}

	/**
	 * Set select FOR UPDATE or LOCK IN SHARE MODE
	 * @param bool $exclusive
	 * @return Result fluent interface
	 */
	public function lock($exclusive = true) {
		$this->lock = $exclusive;
		return $this;
	}

	/**
	 * Union in supported drivers
	 * @param Result $result
	 * @param bool $all
	 * @return Result fluent interface
	 */
	public function union(Result $result, $all = false) {
		$this->union[] = " UNION " . ($all ? "ALL " : "") . ($this->notORM->driver == "sqlite" || $this->notORM->driver == "oci" ? $result : "($result)");
		$this->parameters = array_merge($this->parameters, $result->parameters);
		return $this;
	}

	/**
	 * Execute aggregation function
	 * @param string $function
	 * @return string
	 */
	public function aggregation($function) {
		$join = $this->createJoins(implode(",", $this->conditions) . ",$function");
		$query = "SELECT $function FROM $this->table" . implode($join);
		if (!empty($this->where)) {
			$query .= " WHERE " . implode($this->where);
		}
		foreach ($this->query($query, $this->parameters)->fetch() as $return) {
			return $return;
		}
	}
	
	/**
	 * Count number of rows
	 * @param string $column
	 * @return int
	*/
	public function count($column = "") {
		if (!$column) {
			$this->execute();
			return count($this->data);
		}
		return $this->aggregation("COUNT($column)");
	}
	
	/**
	 * Return minimum value from a column
	 * @param string $column
	 * @return string
	 */
	public function min($column) {
		return $this->aggregation("MIN($column)");
	}
	
	/**
	 * Return maximum value from a column
	 * @param string $column
	 * @return string
	 */
	public function max($column) {
		return $this->aggregation("MAX($column)");
	}
	
	/**
	 * Return sum of values in a column
	 * @param string $column
	 * @return string
	 */
	public function sum($column) {
		return $this->aggregation("SUM($column)");
	}

	/**
	 * Execute the built query
	 * @return null
	 * @throws null
	 */
	protected function execute() {
		if (!isset($this->rows)) {
			$result = false;
			$exception = null;
			$parameters = array();
			foreach (array_merge($this->select, array($this, $this->group, $this->having), $this->order, $this->unionOrder) as $val) {
				if (($val instanceof Literal || $val instanceof self) && $val->parameters) {
					$parameters = array_merge($parameters, $val->parameters);
				}
			}
			try {
				$result = $this->query($this->__toString(), $parameters);
			} catch (\PDOException $exception) {
				// handled later
			}
			if (!$result) {
				if (empty($this->select) && $this->accessed) {
					$this->accessed = '';
					$this->access = array();
					$result = $this->query($this->__toString(), $parameters);
				} elseif ($exception) {
					throw $exception;
				}
			}
			$this->rows = array();
			if ($result) {
				$result->setFetchMode(\PDO::FETCH_ASSOC);
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
	
	/**
	 * Fetch next row of result
	 * @param string $column name to return or an empty string for the whole row
	 * @return mixed string|null with $column, Row without $column, false if there is no row
	 */
	public function fetch($column = '') {
		// no $this->select($column) because next calls can access different columns
		$this->execute();
		$return = current($this->data);
		next($this->data);
		if ($return && $column != '') {
			return $return[$column];
		}
		return $return;
	}
	
	/**
	 * Fetch all rows as associative array
	 * @param string $key
	 * @param string $value column name used for an array value or an empty string for the whole row
	 * @return array
	 */
	public function fetchPairs($key, $value = '') {
		$return = array();
		$clone = clone $this;
		if ($value != "") {
			$clone->select = array();
			$clone->select("$key, $value"); // MultiResult adds its column
		} elseif (!empty($clone->select)) {
			array_unshift($clone->select, $key);
		} else {
			$clone->select = array("$key, $this->table.*");
		}
		foreach ($clone as $row) {
			$values = array_values(iterator_to_array($row));
			if ($value != "" && $clone instanceof MultiResult) {
				array_shift($values);
			}
			$return[(string) $values[0]] = ($value != "" ? $values[(array_key_exists(1, $values) ? 1 : 0)] : $row); // isset($values[1]) - fetchPairs("id", "id")
		}
		return $return;
	}

	/**
	 * Access
	 * @param $key
	 * @param bool $delete
	 * @return bool
	 */
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
		if (empty($this->select) && $this->accessed && (!isset($key) || !isset($this->accessed[$key]))) {
			$this->accessed = '';
			$this->rows = null;
			return true;
		}
		return false;
	}

	/**
	 * Single
	 */
	protected function single() {
	}

	// Iterator implementation (not IteratorAggregate because $this->data can be changed during iteration)
	
	/**
	 * @inheritdoc
	 */
	public function rewind() {
		$this->execute();
		$this->keys = array_keys($this->data);
		reset($this->keys);
	}
	
	/**
	 * @inheritdoc
	 * @return Row
	 */
	public function current() {
		return $this->data[current($this->keys)];
	}
	
	/**
	 * @inheritdoc
	 * @return string row ID
	 */
	public function key() {
		return current($this->keys);
	}

	/**
	 * @inheritdoc
	 */
	public function next() {
		next($this->keys);
	}

	/**
	 * @inheritdoc
	 */
	public function valid() {
		return current($this->keys) !== false;
	}
	
	// ArrayAccess implementation

	/**
	 * Test if row exists
	 * @param mixed $key
	 * @return bool
	 * @internal param row $string ID or array for where conditions
	 */
	public function offsetExists($key) {
		$row = $this->offsetGet($key);
		return isset($row);
	}

	/**
	 * Get specified row
	 * @param string $key row ID or array for where conditions
	 * @return Row or null if there is no such row
	 * @throws null
	 */
	public function offsetGet($key) {
		if ($this->single && !isset($this->data)) {
			$clone = clone $this;
			if (is_array($key)) {
				$clone->where($key)->limit(1);
			} else {
				$clone->where($this->primary, $key);
			}
			$return = $clone->fetch();
			if ($return) {
				return $return;
			}
		} else {
			$this->execute();
			if (is_array($key)) {
				foreach ($this->data as $row) {
					foreach ($key as $k => $v) {
						if ((isset($v) && $row[$k] !== null ? $row[$k] != $v : $row[$k] !== $v)) {
							continue 2;
						}
					}
					return $row;
				}
			} elseif (isset($this->data[$key])) {
				return $this->data[$key];
			}
		}
	}

	/**
	 * Mimic row
	 * @param mixed $key string ID
	 * @param mixed $value NotORM\Row
	 * @return null
	 * @throws null
	 */
	public function offsetSet($key, $value) {
		$this->execute();
		$this->data[$key] = $value;
	}

	/**
	 * Remove row from result set
	 * @param mixed $key string ID
	 * @return null
	 * @throws null
	 */
	public function offsetUnset($key) {
		$this->execute();
		unset($this->data[$key]);
	}
	
	// JsonSerializable implementation
	/**
	 * @inheritdoc
	 */
	public function jsonSerialize() {
		$this->execute();
		if ($this->notORM->jsonAsArray) {
			return array_values($this->data);
		} else {
			return $this->data;
		}
	}
	
}
