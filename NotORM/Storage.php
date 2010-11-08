<?php

/** Data storage
*/
interface NotORM_Storage {
	
	/** Get data from storage
	* @param ...
	* @return NotORM_Result
	*/
	function select();
	
	/** Insert data to storage
	* @param string table name
	* @param mixed
	* @return auto increment value or false in case of an error
	*/
	function insert($table, $data);
	
	/** Update data in storage
	* @param string
	* @param array
	* @param array conditions with ? or :
	* @param array where values
	* @return int number of affected rows or false in case of an error
	*/
	function update($table, array $data, array $where, array $parameters);
	
	/** Delete data from storage
	* @param string
	* @param array conditions with ? or :
	* @param array where values
	* @return int number of affected rows or false in case of an error
	*/
	function delete($table, array $where, array $parameters);
	
}

class NotORM_Storage_PDO { //! implements NotORM_Storage
	protected $connection;
	
	/** Initialize storage
	* @param PDO
	*/
	function __construct(PDO $connection) {
		$this->connection = $connection;
	}
	
	// temporary implementation
	function __call($name, array $args) {
		return call_user_func_array(array($this->connection, $name), $args);
	}
	
}
