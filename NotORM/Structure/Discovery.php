<?php
namespace NotORM\Structure;

/** Structure reading meta-informations from the database
*/
class Discovery implements \NotORM\Structure {
	protected $connection, $cache, $structure = array();
	protected $foreign;

	/** Create autodisovery structure
	* @param \PDO $connection
	* @param \NotORM\Cache|null $cache
	* @param string $foreign use "%s_id" to access $name . "_id" column in $row->$name
	*/
	function __construct(\PDO $connection, \NotORM\Cache $cache = null, $foreign = '%s') {
		$this->connection = $connection;
		$this->cache = $cache;
		$this->foreign = $foreign;
		if ($cache) {
			$this->structure = $cache->load("structure");
		}
	}

	/** Save data to cache
	*/
	function __destruct() {
		if ($this->cache) {
			$this->cache->save("structure", $this->structure);
		}
	}

	function getPrimary($table) {
		$return = &$this->structure["primary"][$table];
		if (!isset($return)) {
			$return = "";
			foreach ($this->connection->query("EXPLAIN $table", \PDO::FETCH_NUM) as $column) {
				if ($column[3] == "PRI") { // 3 - "Key" is not compatible with PDO::CASE_LOWER
					if ($return != "") {
						$return = ""; // multi-column primary key is not supported
						break;
					}
					$return = $column[0];
				}
			}
		}
		return $return;
	}

	function getReferencingColumn($name, $table) {
		$name = strtolower($name);
		$return = &$this->structure["referencing"][$table];
		if (!isset($return[$name])) {
			$query = "
				SELECT TABLE_NAME, COLUMN_NAME
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_NAME = " . $this->connection->quote($table) . "
				AND REFERENCED_COLUMN_NAME = " . $this->connection->quote($this->getPrimary($table)); //! may not reference primary key
			foreach ($this->connection->query($query, \PDO::FETCH_NUM) as $row) {
				$return[strtolower($row[0])] = $row[1];
			}
		}
		return $return[$name];
	}

	function getReferencingTable($name, $table) {
		return $name;
	}

	function getReferencedColumn($name, $table) {
		return sprintf($this->foreign, $name);
	}

	function getReferencedTable($name, $table) {
		$column = strtolower($this->getReferencedColumn($name, $table));
		$return = &$this->structure["referenced"][$table];
		if (!isset($return[$column])) {
			$query = "
				SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_SCHEMA = DATABASE()
				AND TABLE_NAME = " . $this->connection->quote($table) . "
			";
			foreach ($this->connection->query($query, \PDO::FETCH_NUM) as $row) {
				$return[strtolower($row[0])] = $row[1];
			}
		}
		return $return[$column];
	}

	function getSequence($table) {
		return null;
	}

}
