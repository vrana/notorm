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
	protected $primary, $referencedColumn, $referencedTable;
	
	/** Create conventional structure
	* @param string %s stands for table name
	* @param string %1$s stands for key used after ->, %2$s for table name
	* @param string %1$s stands for key used after ->, %2$s for table name
	*/
	function __construct($primary = 'id', $referencedColumn = '%s_id', $referencedTable = '%s') {
		$this->primary = $primary;
		$this->referencedColumn = $referencedColumn;
		$this->referencedTable = $referencedTable;
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
		return sprintf($this->referencedColumn, $name, $table);
	}
	
	function getReferencedTable($name, $table) {
		return sprintf($this->referencedTable, $name, $table);
	}
	
}

/** Structure reading meta-informations from the database
*/
class NotORM_Structure_Discovery_MySQL implements NotORM_Structure {
	private $pdo;
	
	/** Create autodisovery structure
	* @param PDO
	* @param string
	*/
	function __construct(PDO $pdo) {
		$this->pdo = $pdo;
	}
	
	function getPrimary($table) {
		$return = null;
		foreach ($this->pdo->query("EXPLAIN $table") as $column) {
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
		return $this->pdo->query("
			SELECT COLUMN_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = DATABASE()
			AND REFERENCED_TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = " . $this->pdo->quote($name) . "
			AND REFERENCED_TABLE_NAME = " . $this->pdo->quote($table) . "
			AND REFERENCED_COLUMN_NAME = " . $this->pdo->quote($this->getPrimary($table)) . "
		")->fetchColumn(); //! may not reference primary key
	}
	
	function getReferencingTable($name, $table) {
		return $name;
	}
	
	function getReferencedColumn($name, $table) {
		return $name;
	}
	
	function getReferencedTable($name, $table) {
		return $this->pdo->query("
			SELECT REFERENCED_TABLE_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = DATABASE()
			AND REFERENCED_TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = " . $this->pdo->quote($table) . "
			AND COLUMN_NAME = " . $this->pdo->quote($name) . "
		")->fetchColumn();
	}
	
}
