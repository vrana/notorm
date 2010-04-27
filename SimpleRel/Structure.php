<?php
interface SimpleRel_Structure {
	
	/** Get primary key of a table in $db->table()
	* @param string
	* @return string
	*/
	function getPrimary($table);
	
	/** Get column holding foreign key in $table["foreign"]
	* @param string
	* @param string
	* @return string
	*/
	function getForeignColumn($key, $table);
	
	/** Get table referenced by a foreign key in $table["foreign"]
	* @param string
	* @param string
	* @return string
	*/
	function getForeignTable($key, $table);
	
}

class SimpleRel_Structure_Convention implements SimpleRel_Structure {
	protected $primary, $foreignTable, $foreignColumn;
	
	/** Create conventional structure
	* @param string %s stands for table name
	* @param string %1$s stands for key used after ->, %2$s for table name
	* @param string %1$s stands for key used after ->, %2$s for table name
	*/
	function __construct($primary = 'id', $foreignColumn = '%s_id', $foreignTable = '%s') {
		$this->primary = $primary;
		$this->foreignColumn = $foreignColumn;
		$this->foreignTable = $foreignTable;
	}
	
	function getPrimary($table) {
		return sprintf($this->primary, $table);
	}
	
	function getForeignColumn($key, $table) {
		return sprintf($this->foreignColumn, $key, $table);
	}
	
	function getForeignTable($key, $table) {
		return sprintf($this->foreignTable, $key, $table);
	}
	
}

//! class SimpleRel_Structure_Discovery implements SimpleRel_Structure
