<?php
/** NotORM - simple reading data from the database
* @link https://www.notorm.com/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2010 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/

namespace NotORM;

/** Database representation
* @property-write mixed $debug = false Enable debugging queries, true for error_log($query), callback($query, $parameters) otherwise
* @property-write bool $freeze = false Disable persistence
* @property-write string $rowClass = 'NotORM\Row' Class used for created objects
* @property-write bool $jsonAsArray = false Use array instead of object in Result JSON serialization
* @property-write string $transaction Assign 'BEGIN', 'COMMIT' or 'ROLLBACK' to start or stop transaction
*/
class NotORM extends Base {
	
	/** Create database representation
	* @param \PDO
	* @param Structure or null for new Structure\Convention
	* @param Cache or null for no cache
	*/
	function __construct(\PDO $connection, Structure $structure = null, Cache $cache = null) {
		$this->connection = $connection;
		$this->driver = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
		if (!isset($structure)) {
			$structure = new Structure\Convention;
		}
		$this->structure = $structure;
		$this->cache = $cache;
	}
	
	/** Get table data to use as $db->table[1]
	* @param string
	* @return Result
	*/
	function __get($table) {
		return new Result($this->structure->getReferencingTable($table, ''), $this, true);
	}
	
	/** Set write-only properties
	* @return null
	*/
	function __set($name, $value) {
		if ($name == "debug" || $name == "debugTimer" || $name == "freeze" || $name == "rowClass" || $name == "jsonAsArray") {
			$this->$name = $value;
		}
		if ($name == "transaction") {
			switch (strtoupper($value)) {
				case "BEGIN": return $this->connection->beginTransaction();
				case "COMMIT": return $this->connection->commit();
				case "ROLLBACK": return $this->connection->rollback();
			}
		}
	}
	
	/** Get table data
	* @param string
	* @param array (["condition"[, array("value")]]) passed to Result::where()
	* @return Result
	*/
	function __call($table, array $where) {
		$return = new Result($this->structure->getReferencingTable($table, ''), $this);
		if ($where) {
			call_user_func_array(array($return, 'where'), $where);
		}
		return $return;
	}
	
}
