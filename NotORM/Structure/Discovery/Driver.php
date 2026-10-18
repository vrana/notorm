<?php
namespace NotORM\Structure\Discovery;

/** Reading primary and foreign keys from the database metadata, only single-column keys are supported
*/
abstract class Driver {
	protected $connection;

	/**
	* @param \PDO $connection
	*/
	function __construct(\PDO $connection) {
		$this->connection = $connection;
	}

	/** Get columns of the primary key
	* @param string $table
	* @return list<string>
	*/
	abstract function primaryColumns($table);

	/** Get foreign keys pointing to the primary key of $table
	* @param string $table
	* @param string $primary primary key of $table
	* @return list<list<string>> array(referencing table, referencing column)
	*/
	abstract function referencingColumns($table, $primary);

	/** Get foreign keys in $table
	* @param string $table
	* @return list<list<string>> array(column, referenced table)
	*/
	abstract function referencedTables($table);

	/** Get sequence of the primary key, used by insert
	* @param string $table
	* @param string $primary
	* @return string|null|false null if the name is not needed, false if there is none
	*/
	function sequence($table, $primary) {
		return null;
	}

	/** Execute a metadata query
	* @param string $query
	* @param string ...$parameters
	* @return list<list<mixed>>
	*/
	protected function rows($query, ...$parameters) {
		$result = $this->connection->prepare($query);
		$result->execute($parameters);
		return $result->fetchAll(\PDO::FETCH_NUM);
	}

	/** Get the first column of rows
	* @param list<list<mixed>> $rows
	* @return list<string>
	*/
	protected function column(array $rows) {
		$return = array();
		foreach ($rows as $row) {
			$return[] = (string) $row[0];
		}
		return $return;
	}

}
