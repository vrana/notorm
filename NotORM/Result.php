<?php declare(strict_types=1);

namespace NotORM;

/** Filtered table representation
* @implements \Iterator<string|int, Row>
* @implements \ArrayAccess<string|int, Row>
*/
class Result extends Base implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable {
	/** @var list<string|Literal> */ protected array $select = [];
	/** @var list<string> */ protected array $conditions = [];
	/** @var list<string> */ protected array $where = [];
	/** @var mixed[] */ protected array $parameters = [];
	/** @var list<string|Literal> */ protected array $order = [];
	protected ?int $limit = null;
	protected ?int $offset = null;
	protected string|Literal $group = "";
	protected string|Literal $having = "";
	protected ?bool $lock = null;

	/** @var list<Literal> */ protected array $union = [];
	/** @var list<string|Literal> */ protected array $unionOrder = [];
	protected ?int $unionLimit = null;
	protected ?int $unionOffset = null;

	/** @var array<Row>|null */ protected ?array $data = null;
	/** @var array<string, array<array<Row>>> */ protected array $referencing = []; // SQL => referencing column value => rows
	/** @var array<string, array<array<string, mixed>>> */ protected array $aggregation = []; // SQL => referencing column value => row
	/** @var array<bool>|''|null */ protected array|string|null $accessed = null;
	/** @var array<bool>|''|null */ protected array|string|null $access = null;
	/** @var list<string|int> */ protected array $keys = [];
	protected string $alias; // name of the table in the query, e.g. without a prefix

	/** Create table result
	* @param bool $single single row
	* @param ?string $alias name of the table in the query, $table by default
	* @access protected must be public because it is called from NotORM
	*/
	function __construct(
		string $table,
		NotORM $notORM,
		protected bool $single = false,
		?string $alias = null,
	) {
		$this->table = $table;
		$this->alias = $alias ?? $table;
		$this->notORM = $notORM;
		$this->primary = $notORM->structure->getPrimary($table);
	}

	/** Save data to cache and empty result */
	function __destruct() {
		if ($this->notORM->cache && !$this->select && !$this->group && isset($this->rows)) {
			$access = $this->access;
			if (is_array($access)) {
				$access = array_filter($access);
			}
			$this->notORM->cache->save(Cache::VERSION . ";$this->table;" . implode(",", $this->conditions), $access);
		}
		$this->rows = null;
		unset($this->data);
	}

	protected function limitString(?int $limit, ?int $offset = null): string {
		$return = "";
		if (isset($limit)) {
			if ($this->notORM->driver == "oci") {
				$return .= ($offset ? " OFFSET $offset ROWS" : "") . " FETCH NEXT $limit ROWS ONLY";
			} elseif (!in_array($this->notORM->driver, ["dblib", "mssql", "sqlsrv"], true)) {
				$return .= " LIMIT $limit";
				if ($offset) {
					$return .= " OFFSET $offset";
				}
			} elseif ($offset) { // requires ORDER BY
				$return .= " OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
			}
		}
		return $return;
	}

	protected function removeExtraDots(string $expression): string {
		return preg_replace('~(?:\\b[a-z_][a-z0-9_.:]*[.:])?([a-z_][a-z0-9_]*)[.:]([a-z_*])~i', '\\1.\\2', $expression); // rewrite tab1.tab2.col
	}

	protected function whereString(): string {
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

		if ($this->where) {
			$return = " WHERE " . implode($this->where) . $return;
		}

		$return .= $this->limitString($this->limit, $this->offset);
		if (isset($this->lock)) {
			$return .= ($this->lock ? " FOR UPDATE" : " LOCK IN SHARE MODE");
		}
		return $return;
	}

	/** WHERE for UPDATE and DELETE */
	protected function writeString(): string {
		//! the main table has no alias here, so conditions can't use its name if it differs from the table
		if ($this->notORM->driver == "mysql") { // supports ORDER BY and LIMIT in UPDATE and DELETE
			return $this->whereString();
		}
		if (isset($this->limit)) {
			return " WHERE $this->primary IN (SELECT" . $this->topString($this->limit, $this->offset) . " $this->primary FROM $this->table" . $this->whereString() . ")";
		}
		$order = $this->order;
		$this->order = []; // doesn't matter without LIMIT
		$return = $this->whereString();
		$this->order = $order;
		return $return;
	}

	protected function topString(?int $limit, ?int $offset = null): string {
		if (isset($limit) && !$offset && in_array($this->notORM->driver, ["dblib", "mssql", "sqlsrv"], true)) { // offset is in limitString()
			return " TOP ($limit)";
		}
		return "";
	}

	/** Wrap a part of UNION so that it can contain ORDER BY and LIMIT */
	protected function unionPart(string $query): string {
		// SQLite and Oracle don't support parentheses around the parts, MS SQL doesn't support ORDER BY in them
		return (in_array($this->notORM->driver, ["sqlite", "oci", "dblib", "mssql", "sqlsrv"], true) ? "SELECT * FROM ($query) t" : "($query)");
	}

	/** @return array<string, string> */
	protected function createJoins(string $val): array {
		$return = [];
		preg_match_all('~\\b([a-z_][a-z0-9_.:]*[.:])[a-z_*]~i', $val, $matches);
		foreach ($matches[1] as $names) {
			$parent = $this->alias;
			$parentTable = $this->table;
			if ($names != "$parent.") { // case-sensitive
				preg_match_all('~\\b([a-z_][a-z0-9_]*)([.:])~i', $names, $matches, PREG_SET_ORDER);
				foreach ($matches as $match) {
					[, $name, $delimiter] = $match;
					$structure = $this->notORM->structure;
					if ($delimiter == ':') { // the same as $row->$name()
						$table = $structure->getReferencingTable($name, $parentTable);
						$column = $structure->getPrimary($parentTable);
						$primary = $structure->getReferencingColumn($table, $parentTable);
					} else { // the same as $row->$name
						$table = $structure->getReferencedTable($name, $parentTable);
						$column = $structure->getReferencedColumn($name, $parentTable);
						$primary = $structure->getPrimary($table);
					}
					$return[$name] = " LEFT JOIN " . $this->tableAlias($table, $name) . " ON $parent.$column = $name.$primary"; //! a name used on more places needs a unique alias
					$parent = $name;
					$parentTable = $table;
				}
			}
		}
		return $return;
	}

	/** Table with an alias if they differ, Oracle doesn't support AS */
	protected function tableAlias(string $table, string $alias): string {
		return $table . ($table != $alias ? " $alias" : "");
	}

	/** Get SQL query */
	function __toString(): string {
		$return = "SELECT" . $this->topString($this->limit, $this->offset) . " ";
		$join = $this->createJoins(implode(",", $this->conditions) . "," . implode(",", $this->select) . ",$this->group,$this->having," . implode(",", $this->order));
		if (!isset($this->rows) && $this->notORM->cache && !is_string($this->accessed)) {
			$this->accessed = $this->notORM->cache->load(Cache::VERSION . ";$this->table;" . implode(",", $this->conditions));
			$this->access = $this->accessed;
		}
		if ($this->select) {
			$return .= $this->removeExtraDots(implode(", ", $this->select));
		} elseif ($this->group) { // other columns are not allowed with ONLY_FULL_GROUP_BY and in other databases
			$return .= $this->removeExtraDots((string) $this->group); // (string) - Literal
		} elseif ($this->accessed) {
			$return .= ($join ? "$this->alias." : "") . implode(", " . ($join ? "$this->alias." : ""), array_keys($this->accessed));
		} else {
			$return .= ($join ? "$this->alias." : "") . "*";
		}
		$return .= " FROM " . $this->tableAlias($this->table, $this->alias) . implode($join) . $this->whereString();
		if ($this->union) {
			$return = $this->unionPart($return) . implode($this->union);
			if ($this->unionOrder && $this->notORM->driver == "oci") {
				$return = "SELECT * FROM ($return)"; // Oracle doesn't find the columns of the SELECT * parts in ORDER BY
			}
			$top = $this->topString($this->unionLimit, $this->unionOffset);
			if ($top) {
				$return = "SELECT$top * FROM ($return) t"; // ORDER BY outside, MS SQL doesn't allow it in a derived table without TOP
			}
			if ($this->unionOrder) {
				$return .= " ORDER BY " . implode(", ", $this->unionOrder);
			}
			$return .= $this->limitString($this->unionLimit, $this->unionOffset);
		}
		return $return;
	}

	/** @param mixed[] $parameters */
	protected function query(string $query, array $parameters): \PDOStatement|false {
		if ($this->notORM->debug) {
			if (!is_callable($this->notORM->debug)) {
				$debug = "$query;";
				if ($parameters) {
					$debug .= " -- " . implode(", ", array_map([$this, 'quote'], $parameters));
				}
				$pattern = '(^' . preg_quote(__DIR__) . '(\\.php$|[/\\\\]))'; // can be static
				foreach (debug_backtrace() as $backtrace) {
					if (isset($backtrace["file"]) && !preg_match($pattern, $backtrace["file"])) { // stop on first file outside NotORM source codes
						break;
					}
				}
				error_log("$backtrace[file]:$backtrace[line]:$debug\n");
			} elseif (($this->notORM->debug)($query, $parameters) === false) {
				return false;
			}
		}
		$return = $this->notORM->connection->prepare($query);
		if (!$return || !$return->execute(array_map([$this, 'formatValue'], $parameters))) {
			$return = false;
		}
		if ($this->notORM->debugTimer) {
			($this->notORM->debugTimer)();
		}
		return $return;
	}

	protected function formatValue(mixed $val): mixed {
		if ($val instanceof \DateTime) {
			return $val->format("Y-m-d H:i:s"); //! may be driver specific
		}
		return $val;
	}

	protected function quote(mixed $val): string {
		if (!isset($val)) {
			return "NULL";
		}
		if (is_array($val)) { // (a, b) IN ((1, 2), (3, 4))
			return "(" . implode(", ", array_map([$this, 'quote'], $val)) . ")";
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
		if (is_string($val) && (!preg_match('~~u', $val) || str_contains($val, "\0"))) { // binary data, PDO::quote() fails or mangles it
			$hex = bin2hex($val);
			return match ($this->notORM->driver) {
				"pgsql" => "decode('$hex', 'hex')",
				"oci" => "HEXTORAW('$hex')", // up to 2000 bytes
				"dblib", "mssql", "sqlsrv" => "0x$hex",
				default => "X'$hex'",
			};
		}
		return $this->notORM->connection->quote((string) $val); // (string) - Row
	}

	/** Insert rows in one query
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
		$parameters = [];
		if ($data instanceof Result) {
			$parameters = $data->queryParameters();
			$data = (string) $data;
		} elseif ($data instanceof \Traversable) {
			$data = iterator_to_array($data);
		}
		$command = "INSERT INTO $this->table";
		$insert = $data;
		if (is_array($data)) {
			$values = [];
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
			$columns = "(" . implode(", ", array_keys($data)) . ")";
			$insert = ($data || $this->notORM->driver == "mysql"
				? "$columns VALUES " . implode(", ", $values)
				: "DEFAULT VALUES"
			);
			if ($this->notORM->driver == "oci" && count($values) > 1) { // more rows in VALUES are supported since Oracle 23
				$command = "INSERT ALL INTO $this->table";
				$insert = "$columns VALUES " . implode(" INTO $this->table $columns VALUES ", $values) . " SELECT * FROM DUAL";
			}
		}
		// requires empty $this->parameters
		return $this->insertQuery("$command $insert", $parameters);
	}

	/** Execute INSERT
	* @param mixed[] $parameters
	* @return int|false number of affected rows or false in case of an error
	*/
	protected function insertQuery(string $query, array $parameters): int|false {
		$return = $this->query($query, $parameters);
		if (!$return) {
			return false;
		}
		$this->rows = null;
		return $return->rowCount();
	}

	/** Insert row in a table
	* @param iterable<string, mixed>|Result|string $data [$column => $value]|Traversable for single row insert or Result|string for INSERT ... SELECT
	* @param iterable<string, mixed> ...$rows used for extended insert
	* @return Row|int|false inserted Row or false in case of an error or number of affected rows for INSERT ... SELECT
	*/
	function insert(iterable|Result|string $data, iterable ...$rows): Row|int|false {
		$return = $this->insertMulti([$data, ...$rows]);
		if (!$return) {
			return false;
		}
		if (!is_array($data)) {
			return $return;
		}
		$sequence = $this->notORM->structure->getSequence($this->table);
		// lastInsertId() is not supported by oci
		if (is_string($this->primary) && !isset($data[$this->primary]) && $sequence !== '' && $this->notORM->driver != "oci" && ($id = $this->notORM->connection->lastInsertId($sequence))) {
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
		$values = [];
		$parameters = [];
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
		$return = $this->query("UPDATE $this->table SET " . implode(", ", $values) . $this->writeString(), $parameters);
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
	function insertUpdate(array $unique, array $insert, array $update = []): int|false {
		if ($this->notORM->freeze) {
			return false;
		}
		if (!$update) {
			$update = $insert;
		}
		$insert = $unique + $insert;
		$query = "INSERT INTO $this->table (" . implode(", ", array_keys($insert)) . ") VALUES " . $this->quote($insert);
		$parameters = [];
		foreach ($insert as $val) {
			if ($val instanceof Literal) {
				$parameters = array_merge($parameters, $val->parameters);
			}
		}
		if ($this->notORM->driver == "mysql") {
			$set = [];
			if (!$update) {
				$update = $unique;
			}
			foreach ($update as $key => $val) {
				$set[] = "$key = " . $this->quote($val);
				if ($val instanceof Literal) {
					$parameters = array_merge($parameters, $val->parameters);
				}
			}
			return $this->insertQuery("$query ON DUPLICATE KEY UPDATE " . implode(", ", $set), $parameters);
		} else {
			$connection = $this->notORM->connection;
			$errorMode = $connection->getAttribute(\PDO::ATTR_ERRMODE);
			$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			try {
				$return = $this->insertQuery($query, $parameters);
				$connection->setAttribute(\PDO::ATTR_ERRMODE, $errorMode);
				return $return;
			} catch (\PDOException $e) {
				$connection->setAttribute(\PDO::ATTR_ERRMODE, $errorMode);
				if ($e->getCode() == "23000" || $e->getCode() == "23505" || ($this->notORM->driver == "oci" && ($e->errorInfo[1] ?? null) == 1)) { // duplicate key, "23505" in pgsql, ORA-00001 in oci
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
		$return = $this->query("DELETE FROM $this->table" . $this->writeString(), $this->parameters);
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}

	/** Add select clause, more calls appends to the end
	* @param string|Literal $columns for example "column, MD5(column) AS column_md5", empty string to reset previously set columns
	* @return $this fluent interface
	*/
	function select(string|Literal $columns, string|Literal ...$moreColumns): static {
		$this->__destruct();
		if ($columns != "") {
			foreach ([$columns, ...$moreColumns] as $column) {
				$this->select[] = $column;
			}
		} else {
			$this->select = [];
		}
		return $this;
	}

	/** Add where condition, more calls appends with AND
	* @param string|array<string, mixed> $condition string possibly containing ? or :name; or [$condition => $parameters, ...]
	* @param mixed $parameters array accepted by PDOStatement::execute or a scalar value
	* @param mixed ...$moreParameters scalar values
	* @return $this fluent interface
	*/
	function where(string|array $condition, mixed $parameters = [], mixed ...$moreParameters): static {
		$args = func_get_args();
		return $this->whereOperator("AND", $args);
	}

	/** Add AND condition, same as where()
	* @param string|array<string, mixed> $condition
	* @return $this fluent interface
	*/
	function and(string|array $condition, mixed $parameters = [], mixed ...$moreParameters): static {
		$args = func_get_args();
		return $this->whereOperator("AND", $args);
	}

	/** Add OR condition
	* @param string|array<string, mixed> $condition
	* @return $this fluent interface
	*/
	function or(string|array $condition, mixed $parameters = [], mixed ...$moreParameters): static {
		$args = func_get_args();
		return $this->whereOperator("OR", $args);
	}

	/**
	* @param list<mixed> $args
	* @return $this
	*/
	protected function whereOperator(string $operator, array $args): static {
		$condition = $args[0];
		$parameters = (count($args) > 1 ? $args[1] : []);
		if (is_array($condition)) { // where(["column1" => 1, "column2 > ?" => 2])
			foreach ($condition as $key => $val) {
				$this->where($key, $val);
			}
			return $this;
		}
		$this->__destruct();
		$this->conditions[] = "$operator $condition";
		$condition = $this->removeExtraDots($condition);
		if (count($args) != 2 || strpbrk($condition, "?:")) { // where("column < ? OR column > ?", [1, 2])
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
				$this->parameters = array_merge($this->parameters, $clone->queryParameters());
			} else {
				$in = [];
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
		} else { // where("column", [1, 2])
			$condition = $this->whereIn($condition, $parameters);
		}
		$this->where[] = (preg_match('~^\)+$~', $condition)
			? $condition
			: ($this->where ? " $operator " : "") . "($condition)"
		);
		return $this;
	}

	/** @param mixed[] $parameters */
	protected function whereIn(string $condition, array $parameters): string {
		if (!$parameters) {
			// IN () is not supported, it would be NULL for NULL and false otherwise
			$condition = (preg_match('~^NOT\s+(.+)~is', $condition, $match)
				? "$match[1] IS NOT NULL" // NOT is not applicable to other types than bool in PostgreSQL
				: "($condition) IS NOT NULL AND $condition IS NULL"
			);
		} else {
			$column = $condition;
			if ($this->notORM->driver != "oci") {
				$condition .= " IN " . $this->quote($parameters);
			} else { // https://docs.oracle.com/cd/B19306_01/server.102/b14200/expressions014.htm
				$or = [];
				for ($i=0; $i < count($parameters); $i += 1000) {
					$or[] = "$column IN " . $this->quote(array_slice($parameters, $i, 1000));
				}
				$condition = implode(" OR ", $or);
			}
			$nulls = array_filter($parameters, 'is_null');
			if ($nulls) {
				$condition = "$condition OR $column IS NULL";
			}
		}
		return $condition;
	}

	/** Shortcut for where()
	* @param string|array<string, mixed> $where
	* @return $this fluent interface
	*/
	function __invoke(string|array $where, mixed $parameters = [], mixed ...$moreParameters): static {
		$args = func_get_args();
		return $this->whereOperator("AND", $args);
	}

	/** Add order clause, more calls appends to the end
	* @param string|Literal|list<string|Literal> $columns "column1, column2 DESC" or ["column1", "column2 DESC"], empty string to reset previous order
	* @return $this fluent interface
	*/
	function order(string|Literal|array $columns, string|Literal ...$moreColumns): static {
		$this->rows = null;
		if ($columns != "") {
			$columns = (is_array($columns) ? $columns : [$columns, ...$moreColumns]);
			foreach ($columns as $column) {
				if ($this->union) {
					$this->unionOrder[] = $column;
				} else {
					$this->order[] = $column;
				}
			}
		} elseif ($this->union) {
			$this->unionOrder = [];
		} else {
			$this->order = [];
		}
		return $this;
	}

	/** Set limit clause, more calls rewrite old values
	* @return $this fluent interface
	*/
	function limit(int $limit, ?int $offset = null): static {
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
	function group(string|Literal $columns, string|Literal $having = ""): static {
		$this->__destruct();
		$this->group = $columns;
		$this->having = $having;
		return $this;
	}

	/** Set select FOR UPDATE or LOCK IN SHARE MODE
	* @return $this fluent interface
	*/
	function lock(bool $exclusive = true): static {
		$this->lock = $exclusive;
		return $this;
	}

	/**
	* @return $this fluent interface
	*/
	function union(Result $result, bool $all = false): static {
		$this->union[] = new Literal(" UNION " . ($all ? "ALL " : "") . $this->unionPart((string) $result), ...$result->queryParameters());
		return $this;
	}

	/** Execute aggregation function */
	function aggregation(string $function): string|int|float|null {
		if ($this->union || $this->group || isset($this->limit)) { // aggregate the rows of the whole query
			$derived = $this->derived();
			$result = $this->query("SELECT $function FROM ($derived) t", $derived->queryParameters());
		} else {
			$join = $this->createJoins(implode(",", $this->conditions) . ",$function");
			$query = "SELECT $function FROM " . $this->tableAlias($this->table, $this->alias) . implode($join);
			if ($this->where) {
				$query .= " WHERE " . implode($this->where);
			}
			$result = $this->query($query, $this->parameters);
		}
		if ($result) {
			foreach ($result->fetch() as $return) {
				return $return;
			}
		}
		return null;
	}

	/** Clone the query for use as a derived table */
	protected function derived(): static {
		$return = clone $this;
		$return->rows = null; // don't save the accessed columns to the cache
		$return->accessed = ''; // all columns
		if (!isset($return->limit)) {
			$return->order = []; // MS SQL doesn't allow ORDER BY in a derived table without TOP or OFFSET
		}
		if (!isset($return->unionLimit)) {
			$return->unionOrder = [];
		}
		return $return;
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

	/** Get parameters of the query in the order of their placeholders
	* @return mixed[]
	*/
	protected function queryParameters(): array {
		$return = [];
		foreach (array_merge($this->select, [$this, $this->group, $this->having], $this->order, $this->union, $this->unionOrder) as $val) {
			if (($val instanceof Literal || $val instanceof self) && $val->parameters) {
				$return = array_merge($return, $val->parameters);
			}
		}
		return $return;
	}

	/** Execute the built query */
	protected function execute(): void {
		if (!isset($this->rows)) {
			$result = false;
			$exception = null;
			$parameters = $this->queryParameters();
			try {
				$result = $this->query($this->__toString(), $parameters);
			} catch (\PDOException $exception) {
				// handled later
			}
			if (!$result) {
				if (!$this->select && !$this->group && $this->accessed) {
					$this->accessed = '';
					$this->access = [];
					$result = $this->query($this->__toString(), $parameters);
				} elseif ($exception) {
					throw $exception;
				}
			}
			$rows = [];
			if ($result) {
				$result->setFetchMode(\PDO::FETCH_ASSOC);
				foreach ($result as $row) {
					foreach ((array) $this->primary as $column) {
						if (isset($row[$column]) && !is_string($this->access)) {
							$this->access[$column] = true;
						}
					}
					$rows[] = new $this->notORM->rowClass($row, $this);
				}
			}
			$this->rows = $this->index($rows);
			$this->data = $this->rows;
		}
	}

	/** Index rows by their primary key
	* @param list<Row> $rows
	* @return array<Row> $rows if the primary key has more columns or some is missing or duplicate (e.g. from a backwards join)
	*/
	protected function index(array $rows): array {
		$ids = array_map(fn ($row) => $row->id, $rows);
		return (is_array($this->primary) || in_array(null, $ids, true) || count(array_unique($ids)) < count($ids) ? $rows : array_combine($ids, $rows));
	}

	/** Fetch next row of result
	* @param string $column column name to return or an empty string for the whole row
	* @return mixed value with $column, Row without $column, null if there is no row
	*/
	function fetch(string $column = ''): mixed {
		// no $this->select($column) because next calls can access different columns
		$this->execute();
		$return = current($this->data);
		next($this->data);
		if ($return === false) { // no more rows
			return null;
		}
		return ($column != '' ? $return[$column] : $return);
	}

	/** Fetch all rows as associative array
	* @param string|list<string> $key column name or names for nested arrays
	* @param string $value column name used for an array value or an empty string for the whole row
	* @return mixed[] values of $value or Row without it, indexed by $key
	*/
	function fetchPairs(string|array $key, string $value = ''): array {
		$keys = (array) $key;
		$return = [];
		$clone = clone $this;
		if ($value != "") {
			$clone->select = [];
			$clone->select(implode(", ", (in_array($value, $keys, true) ? $keys : [...$keys, $value]))); // MultiResult adds its column, MS SQL doesn't allow ORDER BY a column selected twice
		} elseif ($clone->select) {
			array_unshift($clone->select, implode(", ", $keys));
		} else {
			$clone->select = [implode(", ", $keys) . ", $this->alias.*"];
		}
		$index = array_search($value, $keys, true);
		$index = ($index === false ? count($keys) : $index); // position of $value
		foreach ($clone as $row) {
			$values = array_values(iterator_to_array($row)); // by position - the keys can be expressions
			if ($value != "" && $clone instanceof MultiResult) {
				array_shift($values);
			}
			$node = &$return;
			foreach (array_slice($values, 0, count($keys)) as $val) {
				$node = &$node[(string) $val];
			}
			$node = ($value != "" ? $values[(array_key_exists($index, $values) ? $index : 0)] : $row); // the value has the same name as a key in fetchPairs("id", "application.id")
		}
		unset($node);
		return $return;
	}

	protected function access(string|int|null $key, bool $delete = false): bool {
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
	* @param string|int|array<string, mixed> $offset row ID or array for where conditions
	*/
	function offsetExists(mixed $offset): bool {
		$row = $this->offsetGet($offset);
		return isset($row);
	}

	/** Get specified row
	* @param string|int|array<string, mixed> $offset row ID or array for where conditions
	* @return Row|null null if there is no such row
	*/
	function offsetGet(mixed $offset): mixed {
		if ($this->single && !isset($this->data)) {
			$clone = clone $this;
			if (is_array($offset)) {
				$clone->where($offset)->limit(1);
			} else {
				$clone->where($this->primary, $offset);
			}
			$return = $clone->fetch();
			if ($return) {
				return $return;
			}
		} else {
			$this->execute();
			if (is_array($offset)) {
				foreach ($this->data as $row) {
					foreach ($offset as $k => $v) {
						if ((isset($v) && $row[$k] !== null ? $row[$k] != $v : $row[$k] !== $v)) {
							continue 2;
						}
					}
					return $row;
				}
			} else {
				return $this->data[$offset] ?? null;
			}
		}
		return null;
	}

	/** Mimic row
	* @param string|int|null $offset row ID
	* @param Row $value
	*/
	function offsetSet(mixed $offset, mixed $value): void {
		$this->execute();
		$this->data[$offset] = $value;
	}

	/** Remove row from result set
	* @param string|int $offset row ID
	*/
	function offsetUnset(mixed $offset): void {
		$this->execute();
		unset($this->data[$offset]);
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
