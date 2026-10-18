<?php declare(strict_types=1);

/** NotORM - simple reading data from the database
* @link https://www.notorm.com/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2010 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/

namespace NotORM;

/** Database representation
* @property bool|callable $debug = false Enable debugging queries, true for error_log($query), callback($query, $parameters) otherwise
* @property callable|null $debugTimer Callback called after executing each query, e.g. to measure its time
* @property bool $freeze = false Disable persistence
* @property class-string<Row> $rowClass = 'NotORM\Row' Class used for created objects
* @property bool $jsonAsArray = false Use array instead of object in Result JSON serialization
* @property-write string $transaction Assign 'BEGIN', 'COMMIT' or 'ROLLBACK' to start or stop transaction
*/
class NotORM extends Base {

	/** Create database representation
	* @param Structure|null $structure null for new Structure\Convention
	* @param Cache|null $cache null for no cache
	*/
	function __construct(\PDO $connection, ?Structure $structure = null, ?Cache $cache = null) {
		$this->connection = $connection;
		$this->driver = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
		if (!isset($structure)) {
			$structure = new Structure\Convention;
		}
		$this->structure = $structure;
		$this->cache = $cache;
	}

	/** Get table data to use as $db->table[1] */
	function __get(string $table): Result {
		return new Result($this->structure->getReferencingTable($table, ''), $this, true);
	}

	/** Set write-only properties */
	function __set(string $name, mixed $value): void {
		if ($name == "debug" || $name == "debugTimer" || $name == "freeze" || $name == "rowClass" || $name == "jsonAsArray") {
			$this->$name = $value;
		}
		if ($name == "transaction") {
			switch (strtoupper($value)) {
				case "BEGIN":
					$this->connection->beginTransaction();
					break;
				case "COMMIT":
					$this->connection->commit();
					break;
				case "ROLLBACK":
					$this->connection->rollback();
					break;
			}
		}
	}

	/** Get table data
	* @param mixed[] $where (["condition"[, array("value")]]) passed to Result::where()
	*/
	function __call(string $table, array $where): Result {
		$return = new Result($this->structure->getReferencingTable($table, ''), $this);
		if ($where) {
			call_user_func_array(array($return, 'where'), $where);
		}
		return $return;
	}

}
