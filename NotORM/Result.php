<?php
namespace NotORM;

/** Filtered table representation
* @method $this and(mixed $condition, mixed $parameters = array()) Add AND condition
* @method $this or(mixed $condition, mixed $parameters = array()) Add OR condition
* @implements \Iterator<string|int, Row>
* @implements \ArrayAccess<string|int, Row>
*/
class Result extends Base implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable {
	protected bool $single;

	protected array $select = array();
	protected array $conditions = array();
	protected array $where = array();
	protected array $parameters = array();
	protected array $order = array();
	protected ?int $limit = null;
	protected ?int $offset = null;
	protected string|Literal $group = "";
	protected string|Literal $having = "";
	protected ?bool $lock = null;

	protected array $union = array();
	protected array $unionOrder = array();
	protected ?int $unionLimit = null;
	protected ?int $unionOffset = null;

	protected ?array $data = null;
	protected array $referencing = array();
	protected array $aggregation = array();
	protected array|string|null $accessed = null;
	protected array|string|null $access = null;
	protected array $keys = array();

	/** Create table result
	* @param bool $single single row
	* @access protected must be public because it is called from NotORM
	*/
	function __construct(string $table, NotORM $notORM, bool $single = false) {
		$this->table = $table;
		$this->notORM = $notORM;
		$this->single = $single;
		$this->primary = $notORM->structure->getPrimary($table);
	}

	/** Save data to cache and empty result */
	function __destruct() {
		if ($this->notORM->cache && !$this->select && !$this->group && isset($this->rows)) {
			$access = $this->access;
			if (is_array($access)) {
				$access = array_filter($access);
			}
			$this->notORM->cache->save("$this->table;" . implode(",", $this->conditions), $access);
		}
		$this->rows = null;
		unset($this->data);
	}

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

	protected function removeExtraDots($expression) {
		return preg_replace('~(?:\\b[a-z_][a-z0-9_.:]*[.:])?([a-z_][a-z0-9_]*)[.:]([a-z_*])~i', '\\1.\\2', $expression); // rewrite tab1.tab2.col
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
		$return = $this->removeExtraDots($return);

		$where = $this->where;
		if (isset($this->limit) && $this->notORM->driver == "oci") {
			//! rownum > doesn't work - requires subselect (see adminer/drivers/oracle.inc.php)
			$where[] = ($where ? " AND " : "") . "(" . ($this->offset ? "rownum > $this->offset AND " : "") . "rownum <= " . ($this->limit + $this->offset) . ")";
		}
		if ($where) {
			$return = " WHERE " . implode($where) . $return;
		}

		$return .= $this->limitString($this->limit, $this->offset);
		if (isset($this->lock)) {
			$return .= ($this->lock ? " FOR UPDATE" : " LOCK IN SHARE MODE");
		}
		return $return;
	}

	protected function topString($limit, $offset = null) {
		if (isset($limit) && ($this->notORM->driver == "dblib" || $this->notORM->driver == "mssql" || $this->notORM->driver == "sqlsrv")) {
			return " TOP ($this->limit)"; //! offset is not supported
		}
		return "";
	}

	/** Wrap a part of UNION so that it can contain ORDER BY and LIMIT */
	protected function unionPart(string $query): string {
		// SQLite and Oracle don't support parentheses around the parts
		return ($this->notORM->driver == "sqlite" || $this->notORM->driver == "oci" ? "SELECT * FROM ($query)" : "($query)");
	}

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

	/** Get SQL query */
	function __toString(): string {
		$return = "SELECT" . $this->topString($this->limit, $this->offset) . " ";
		$join = $this->createJoins(implode(",", $this->conditions) . "," . implode(",", $this->select) . ",$this->group,$this->having," . implode(",", $this->order));
		if (!isset($this->rows) && $this->notORM->cache && !is_string($this->accessed)) {
			$this->accessed = $this->notORM->cache->load("$this->table;" . implode(",", $this->conditions));
			$this->access = $this->accessed;
		}
		if ($this->select) {
			$return .= $this->removeExtraDots(implode(", ", $this->select));
		} elseif ($this->group) { // other columns are not allowed with ONLY_FULL_GROUP_BY and in other databases
			$return .= $this->removeExtraDots($this->group);
		} elseif ($this->accessed) {
			$return .= ($join ? "$this->table." : "") . implode(", " . ($join ? "$this->table." : ""), array_keys($this->accessed));
		} else {
			$return .= ($join ? "$this->table." : "") . "*";
		}
		$return .= " FROM $this->table" . implode($join) . $this->whereString();
		if ($this->union) {
			$return = $this->unionPart($return) . implode($this->union);
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

	protected function query($query, $parameters) {
		if ($this->notORM->debug) {
			if (!is_callable($this->notORM->debug)) {
				$debug = "$query;";
				if ($parameters) {
					$debug .= " -- " . implode(", ", array_map(array($this, 'quote'), $parameters));
				}
				$pattern = '(^' . preg_quote(__DIR__) . '(\\.php$|[/\\\\]))'; // can be static
				foreach (debug_backtrace() as $backtrace) {
					if (isset($backtrace["file"]) && !preg_match($pattern, $backtrace["file"])) { // stop on first file outside NotORM source codes
						break;
					}
				}
				error_log("$backtrace[file]:$backtrace[line]:$debug\n", 0);
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

	protected function formatValue($val) {
		if ($val instanceof \DateTime) {
			return $val->format("Y-m-d H:i:s"); //! may be driver specific
		}
		return $val;
	}

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

	/** Shortcut for call_user_func_array(array($this, 'insert'), $rows)
	* @param list<iterable<string, mixed>|Result|string> $rows
	* @return int|false number of affected rows or false in case of an error
	*/
	function insertMulti(array $rows): int|false {
		if ($this->notORM->freeze) {
			return false;
		}
		if (!$rows) {
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
					if ($val instanceof Literal && $val->parameters) {
						$parameters = array_merge($parameters, $val->parameters);
					}
				}
			}
			//! driver specific extended insert
			$insert = ($data || $this->notORM->driver == "mysql"
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

	/** Insert row in a table
	* @param iterable<string, mixed>|Result|string $data array($column => $value)|Traversable for single row insert or Result|string for INSERT ... SELECT
	* @param iterable<string, mixed> ...$rows used for extended insert
	* @return Row|int|false inserted Row or false in case of an error or number of affected rows for INSERT ... SELECT
	*/
	function insert(iterable|Result|string $data, iterable ...$rows): Row|int|false {
		$return = $this->insertMulti(array_merge(array($data), $rows));
		if (!$return) {
			return false;
		}
		if (!is_array($data)) {
			return $return;
		}
		$sequence = $this->notORM->structure->getSequence($this->table);
		if (!isset($data[$this->primary]) && $sequence !== false && ($id = $this->notORM->connection->lastInsertId($sequence))) {
			$data[$this->primary] = $id;
		}
		return new $this->notORM->rowClass($data, $this);
	}

	/** Update all rows in result set
	* @param array<string, mixed> $data ($column => $value)
	* @return int|false number of affected rows or false in case of an error
	*/
	function update(array $data): int|false {
		if ($this->notORM->freeze) {
			return false;
		}
		if (!$data) {
			return 0;
		}
		$values = array();
		$parameters = array();
		foreach ($data as $key => $val) {
			// doesn't use binding because $this->parameters can be filled by ? or :name
			$values[] = "$key = " . $this->quote($val);
			if ($val instanceof Literal && $val->parameters) {
				$parameters = array_merge($parameters, $val->parameters);
			}
		}
		if ($this->parameters) {
			$parameters = array_merge($parameters, $this->parameters);
		}
		// joins in UPDATE are supported only in MySQL
		$return = $this->query("UPDATE" . $this->topString($this->limit, $this->offset) . " $this->table SET " . implode(", ", $values) . $this->whereString(), $parameters);
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}

	/** Insert row or update if it already exists
	* @param array<string, mixed> $unique ($column => $value)
	* @param array<string, mixed> $insert ($column => $value)
	* @param array<string, mixed> $update ($column => $value), empty array means use $insert
	* @return int|false number of affected rows or false in case of an error
	*/
	function insertUpdate(array $unique, array $insert, array $update = array()): int|false {
		if (!$update) {
			$update = $insert;
		}
		$insert = $unique + $insert;
		$values = "(" . implode(", ", array_keys($insert)) . ") VALUES " . $this->quote($insert);
		//! parameters
		if ($this->notORM->driver == "mysql") {
			$set = array();
			if (!$update) {
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
					if (!$update) {
						return 0;
					}
					$clone = clone $this;
					$return = $clone->where($unique)->update($update);
					return ($return ? $return + 1 : $return);
				}
				if ($errorMode == \PDO::ERRMODE_EXCEPTION) {
					throw $e;
				} elseif ($errorMode == \PDO::ERRMODE_WARNING) {
					trigger_error("PDOStatement::execute(): " . $e->getMessage(), E_USER_WARNING); // E_WARNING is unusable
				}
			}
		}
		return false;
	}

	/** Get last insert ID
	* @return string|false number
	*/
	function insertId(): string|false {
		return $this->notORM->connection->lastInsertId();
	}

	/** Delete all rows in result set
	* @return int|false number of affected rows or false in case of an error
	*/
	function delete(): int|false {
		if ($this->notORM->freeze) {
			return false;
		}
		$return = $this->query("DELETE" . $this->topString($this->limit, $this->offset) . " FROM $this->table" . $this->whereString(), $this->parameters);
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}

	/** Add select clause, more calls appends to the end
	* @param string|Literal $columns for example "column, MD5(column) AS column_md5", empty string to reset previously set columns
	* @return $this fluent interface
	*/
	function select(string|Literal $columns, string|Literal ...$moreColumns) {
		$this->__destruct();
		if ($columns != "") {
			foreach (array_merge(array($columns), $moreColumns) as $column) {
				$this->select[] = $column;
			}
		} else {
			$this->select = array();
		}
		return $this;
	}

	/** Add where condition, more calls appends with AND
	* @param string|array<string, mixed> $condition string possibly containing ? or :name; or array($condition => $parameters, ...)
	* @param mixed $parameters array accepted by PDOStatement::execute or a scalar value
	* @param mixed ...$moreParameters scalar values
	* @return $this fluent interface
	*/
	function where(string|array $condition, mixed $parameters = array(), mixed ...$moreParameters) {
		$args = func_get_args();
		return $this->whereOperator("AND", $args);
	}

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
			if (!$clone->select) {
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
				if ($in) {
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

	protected function whereIn($condition, $parameters) {
		if (!$parameters) {
			// IN () is not supported, it would be NULL for NULL and false otherwise
			$condition = (preg_match('~^NOT\s+(.+)~is', $condition, $match)
				? "$match[1] IS NOT NULL" // NOT is not applicable to other types than bool in PostgreSQL
				: "($condition) IS NOT NULL AND $condition IS NULL"
			);
		} elseif ($this->notORM->driver != "oci") {
			$column = $condition;
			$condition .= " IN " . $this->quote($parameters);
			$nulls = array_filter($parameters, 'is_null');
			if ($nulls) {
				$condition = "$condition OR $column IS NULL";
			}
		} else { // https://docs.oracle.com/cd/B19306_01/server.102/b14200/expressions014.htm
			$or = array();
			for ($i=0; $i < count($parameters); $i += 1000) {
				$or[] = "$condition IN " . $this->quote(array_slice($parameters, $i, 1000));
			}
			$condition = implode(" OR ", $or);
		}
		return $condition;
	}

	function __call($name, array $args) {
		$operator = strtoupper($name);
		switch ($operator) {
			case "AND":
			case "OR":
				return $this->whereOperator($operator, $args);
		}
		throw new \BadMethodCallException("Call to undefined method " . static::class . "::$name()");
	}

	/** Shortcut for where()
	* @param string|array<string, mixed> $where
	* @return $this fluent interface
	*/
	function __invoke(string|array $where, mixed $parameters = array(), mixed ...$moreParameters) {
		$args = func_get_args();
		return $this->whereOperator("AND", $args);
	}

	/** Add order clause, more calls appends to the end
	* @param string|Literal|list<string|Literal> $columns "column1, column2 DESC" or array("column1", "column2 DESC"), empty string to reset previous order
	* @return $this fluent interface
	*/
	function order(string|Literal|array $columns, string|Literal ...$moreColumns) {
		$this->rows = null;
		if ($columns != "") {
			$columns = (is_array($columns) ? $columns : array_merge(array($columns), $moreColumns));
			foreach ($columns as $column) {
				if ($this->union) {
					$this->unionOrder[] = $column;
				} else {
					$this->order[] = $column;
				}
			}
		} elseif ($this->union) {
			$this->unionOrder = array();
		} else {
			$this->order = array();
		}
		return $this;
	}

	/** Set limit clause, more calls rewrite old values
	* @return $this fluent interface
	*/
	function limit(int $limit, ?int $offset = null) {
		$this->rows = null;
		if ($this->union) {
			$this->unionLimit = +$limit;
			$this->unionOffset = +$offset;
		} else {
			$this->limit = +$limit;
			$this->offset = +$offset;
		}
		return $this;
	}

	/** Set group clause, more calls rewrite old values
	* @return $this fluent interface
	*/
	function group(string|Literal $columns, string|Literal $having = "") {
		$this->__destruct();
		$this->group = $columns;
		$this->having = $having;
		return $this;
	}

	/** Set select FOR UPDATE or LOCK IN SHARE MODE
	* @return $this fluent interface
	*/
	function lock(bool $exclusive = true) {
		$this->lock = $exclusive;
		return $this;
	}

	/**
	* @return $this fluent interface
	*/
	function union(Result $result, bool $all = false) {
		$this->union[] = " UNION " . ($all ? "ALL " : "") . $this->unionPart($result);
		$this->parameters = array_merge($this->parameters, $result->parameters);
		return $this;
	}

	/** Execute aggregation function */
	function aggregation(string $function): string|int|float|null {
		$join = $this->createJoins(implode(",", $this->conditions) . ",$function");
		$query = "SELECT $function FROM $this->table" . implode($join);
		if ($this->where) {
			$query .= " WHERE " . implode($this->where);
		}
		$result = $this->query($query, $this->parameters);
		if ($result) {
			foreach ($result->fetch() as $return) {
				return $return;
			}
		}
		return null;
	}

	/** Count number of rows */
	function count(string $column = ""): int {
		if (!$column) {
			$this->execute();
			return count($this->data);
		}
		return (int) $this->aggregation("COUNT($column)"); // null in MultiResult without rows
	}

	/** Return minimum value from a column */
	function min(string $column): string|int|float|null {
		return $this->aggregation("MIN($column)");
	}

	/** Return maximum value from a column */
	function max(string $column): string|int|float|null {
		return $this->aggregation("MAX($column)");
	}

	/** Return sum of values in a column */
	function sum(string $column): string|int|float|null {
		return $this->aggregation("SUM($column)");
	}

	/** Execute the built query */
	protected function execute(): void {
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
				if (!$this->select && !$this->group && $this->accessed) {
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

	/** Fetch next row of result
	* @param string $column column name to return or an empty string for the whole row
	* @return mixed value with $column, Row without $column, false if there is no row
	*/
	function fetch(string $column = ''): mixed {
		// no $this->select($column) because next calls can access different columns
		$this->execute();
		$return = current($this->data);
		next($this->data);
		if ($return && $column != '') {
			return $return[$column];
		}
		return $return;
	}

	/** Fetch all rows as associative array
	* @param string $value column name used for an array value or an empty string for the whole row
	* @return mixed[] values of $value or Row without it, indexed by $key
	*/
	function fetchPairs(string $key, string $value = ''): array {
		$return = array();
		$clone = clone $this;
		if ($value != "") {
			$clone->select = array();
			$clone->select("$key, $value"); // MultiResult adds its column
		} elseif ($clone->select) {
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

	protected function access($key, $delete = false): bool {
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
		if (!$this->select && !$this->group && $this->accessed && (!isset($key) || !isset($this->accessed[$key]))) {
			$this->accessed = '';
			$this->rows = null;
			return true;
		}
		return false;
	}

	protected function single(): void {
	}

	// Iterator implementation (not IteratorAggregate because $this->data can be changed during iteration)

	function rewind(): void {
		$this->execute();
		$this->keys = array_keys($this->data);
		reset($this->keys);
	}

	/** @return Row */
	function current(): mixed {
		return $this->data[current($this->keys)];
	}

	/** @return string|int row ID */
	function key(): mixed {
		return current($this->keys);
	}

	function next(): void {
		next($this->keys);
	}

	function valid(): bool {
		return current($this->keys) !== false;
	}

	// ArrayAccess implementation

	/** Test if row exists
	* @param string|int|array<string, mixed> $key row ID or array for where conditions
	*/
	function offsetExists($key): bool {
		$row = $this->offsetGet($key);
		return isset($row);
	}

	/** Get specified row
	* @param string|int|array<string, mixed> $key row ID or array for where conditions
	* @return Row|null null if there is no such row
	*/
	function offsetGet($key): mixed {
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
		return null;
	}

	/** Mimic row
	* @param string|int|null $key row ID
	* @param Row $value
	*/
	function offsetSet($key, $value): void {
		$this->execute();
		$this->data[$key] = $value;
	}

	/** Remove row from result set
	* @param string|int $key row ID
	*/
	function offsetUnset($key): void {
		$this->execute();
		unset($this->data[$key]);
	}

	// JsonSerializable implementation

	function jsonSerialize(): mixed {
		$this->execute();
		if ($this->notORM->jsonAsArray) {
			return array_values($this->data);
		} else {
			return $this->data;
		}
	}

}
