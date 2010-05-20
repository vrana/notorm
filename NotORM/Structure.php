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
	* @param DibiConnection
	*/
	function __construct(DibiConnection $connection) {
		$this->connection = $connection;
	}
	
	function getPrimary($table) {
		foreach ($this->connection->getDriver()->getIndexes($table) as $index) {
			if ($index['primary'] && count($index['columns']) == 1) {
				return $index['columns'][0];
			}
		}
	}
	
	function getReferencingColumn($name, $table) {
		$primary = $this->getPrimary($table);
		foreach ($this->connection->getDriver()->getForeignKeys($name) as $foreign) {
			if ($foreign["table"] == $table) {
				$key = array_search($primary, $foreign["foreign"]);
				if ($key !== false) {
					return $foreign["local"][$key];
				}
			}
		}
		return false;
	}
	
	function getReferencingTable($name, $table) {
		return $name;
	}
	
	function getReferencedColumn($name, $table) {
		return $name;
	}
	
	function getReferencedTable($name, $table) {
		foreach ($this->connection->getDriver()->getForeignKeys($table) as $foreign) {
			$key = array_search($name, $foreign["local"]);
			if ($key !== false) {
				return $foreign["table"];
			}
		}
		return false;
	}
	
}
