<?php

/** Data storage
*/
interface NotORM_Storage {
	
	/** Get data from storage
	* @param ...
	* @return array|Traversable contains associative arrays
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
	function update($table, array $data, array $where, array $parameters = array());
	
	/** Delete data from storage
	* @param string
	* @param array conditions with ? or :
	* @param array where values
	* @return int number of affected rows or false in case of an error
	*/
	function delete($table, array $where, array $parameters = array());
	
}

/** Storage using PDO
*/
class NotORM_Storage_PDO implements NotORM_Storage {
	protected $connection;
	
	/** Enable debuging queries
	* @var mixed true for fwrite(STDERR, $query), callback($query, $parameters) otherwise
	* @access public write-only
	*/
	public $debug = false;
	
	/** Initialize storage
	* @param PDO
	* @param bool
	*/
	function __construct(PDO $connection, $debug = false) {
		$this->connection = $connection;
		$this->debug = $debug;
	}
	
	//! protected
	function query($query, array $parameters = array()) {
		if ($this->debug) {
			if (is_callable($this->debug)) {
				call_user_func($this->debug, $query, $this->parameters);
			} else {
				fwrite(STDERR, "-- $query;\n");
			}
		}
		$return = $this->connection->prepare($query);
		if (!$return->execute($parameters)) {
			return false;
		}
		$return->setFetchMode(PDO::FETCH_ASSOC);
		return $return;
	}
	
	function select() {
	}
	
	//! protected
	function quote($val) {
		if (!isset($val)) {
			return "NULL";
		}
		if ($val instanceof NotORM_Literal) { // SQL code - for example "NOW()"
			return $val->value;
		}
		return $this->connection->quote($val);
	}
	
	function insert($table, $data) {
		if ($data instanceof NotORM_Result) {
			$data = (string) $data;
		} elseif ($data instanceof Traversable) {
			$data = iterator_to_array($data);
		}
		if (is_array($data)) {
			//! driver specific empty $data
			$data = "(" . implode(", ", array_keys($data)) . ") VALUES (" . implode(", ", array_map(array($this, 'quote'), $data)) . ")";
		}
		// requiers empty $this->parameters
		if (!$this->query("INSERT INTO $table $data")) {
			return false;
		}
		return $this->connection->lastInsertId();
	}
	
	function update($table, array $data, array $where, array $parameters = array()) {
		$values = array();
		foreach ($data as $key => $val) {
			$values[] = "$key = " . $this->quote($val);
		}
		// joins in UPDATE are supported only in MySQL, ORDER and LIMIT not in most engines
		$query = "UPDATE $table SET " . implode(", ", $values);
		if ($where) {
			$query .= " WHERE (" . implode(") AND (", $where) . ")";
		}
		$return = $this->query($query, $parameters);
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}
	
	function delete($table, array $where, array $parameters = array()) {
		$query = "DELETE FROM $table";
		if ($where) {
			$query .= " WHERE (" . implode(") AND (", $where) . ")";
		}
		$return = $this->query($query, $parameters);
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}
	
	//! temporary implementation
	function __call($name, array $args) {
		return call_user_func_array(array($this->connection, $name), $args);
	}
	
}
