<?php

/** Information about tables and columns structure
*/
interface NotORM_Structure {
	
	/** Get primary key of a table in $db->$table()
	* @param string
	* @return string
	*/
	function getPrimary($table);
	
	/** Get column holding foreign key in $table[$id]->$name()
	* @param string
	* @param string
	* @return string
	*/
	function getReferencingColumn($name, $table);
	
	/** Get target table in $table[$id]->$name()
	* @param string
	* @param string
	* @return string
	*/
	function getReferencingTable($name, $table);
	
	/** Get column holding foreign key in $table[$id]->$name
	* @param string
	* @param string
	* @return string
	*/
	function getReferencedColumn($name, $table);
	
	/** Get table holding foreign key in $table[$id]->$name
	* @param string
	* @param string
	* @return string
	*/
	function getReferencedTable($name, $table);
	
}

/** Structure described by some rules
*/
class NotORM_Structure_Convention implements NotORM_Structure {
	protected $primary, $foreign, $table;
	
	/** Create conventional structure
	* @param string %s stands for table name
	* @param string %1$s stands for key used after ->, %2$s for table name
	* @param string %1$s stands for key used after ->, %2$s for table name
	*/
	function __construct($primary = 'id', $foreign = '%s_id', $table = '%s') {
		$this->primary = $primary;
		$this->foreign = $foreign;
		$this->table = $table;
	}
	
	function getPrimary($table) {
		return sprintf($this->primary, $table);
	}
	
	function getReferencingColumn($name, $table) {
		return $this->getReferencedColumn($table, $name);
	}
	
	function getReferencingTable($name, $table) {
		return $name;
	}
	
	function getReferencedColumn($name, $table) {
		if ($this->table != '%s' && preg_match('(^' . str_replace('%s', '(.*)', preg_quote($this->table)) . '$)', $name, $match)) {
			$name = $match[1];
		}
		return sprintf($this->foreign, $name, $table);
	}
	
	function getReferencedTable($name, $table) {
		return sprintf($this->table, $name, $table);
	}
	
}

/** Structure reading meta-informations from the database
*/
class NotORM_Structure_Discovery implements NotORM_Structure {
	private $connection;
	
	/** Create autodisovery structure
	* @param PDO
	* @param string
	*/
	function __construct(PDO $connection) {
		//! test supported drivers by PDO::ATTR_DRIVER_NAME
		$this->connection = $connection;
	}
	
	function getPrimary($table) {
		$return = null;
		foreach ($this->connection->query("EXPLAIN $table") as $column) {
			if ($column["Key"] == "PRI") {
				if (isset($return)) {
					return null; // multi-column primary key is not supported
				}
				$return = $column["Field"];
			}
		}
		return $return;
	}
	
	function getReferencingColumn($name, $table) {
		return $this->connection->query("
			SELECT COLUMN_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = DATABASE()
			AND REFERENCED_TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = " . $this->connection->quote($name) . "
			AND REFERENCED_TABLE_NAME = " . $this->connection->quote($table) . "
			AND REFERENCED_COLUMN_NAME = " . $this->connection->quote($this->getPrimary($table)) . "
		")->fetchColumn(); //! may not reference primary key
	}
	
	function getReferencingTable($name, $table) {
		return $name;
	}
	
	function getReferencedColumn($name, $table) {
		return $name;
	}
	
	function getReferencedTable($name, $table) {
		return $this->connection->query("
			SELECT REFERENCED_TABLE_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = DATABASE()
			AND REFERENCED_TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = " . $this->connection->quote($table) . "
			AND COLUMN_NAME = " . $this->connection->quote($name) . "
		")->fetchColumn();
	}
	
}
