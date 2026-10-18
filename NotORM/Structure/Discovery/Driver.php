<?php declare(strict_types=1);

namespace NotORM\Structure\Discovery;

/** Reading primary and foreign keys from the database metadata, only single-column keys are supported */
abstract class Driver {
	protected \PDO $connection;

	function __construct(\PDO $connection) {
		$this->connection = $connection;
	}

	/** Get columns of the primary key
	* @return list<string>
	*/
	abstract function primaryColumns(string $table): array;

	/** Get foreign keys pointing to the primary key of $table
	* @param string $primary primary key of $table
	* @return list<list<string>> array(referencing table, referencing column)
	*/
	abstract function referencingColumns(string $table, string $primary): array;

	/** Get foreign keys in $table
	* @return list<list<string>> array(column, referenced table)
	*/
	abstract function referencedTables(string $table): array;

	/** Get sequence of the primary key, used by insert
	* @return string|null|false null if the name is not needed, false if there is none
	*/
	function sequence(string $table, string $primary): string|false|null {
		return null;
	}

	/** Execute a metadata query
	* @return list<list<mixed>>
	*/
	protected function rows(string $query, string ...$parameters): array {
		$result = $this->connection->prepare($query);
		$result->execute($parameters);
		return $result->fetchAll(\PDO::FETCH_NUM);
	}

	/** Get the first column of rows
	* @param list<list<mixed>> $rows
	* @return list<string>
	*/
	protected function column(array $rows): array {
		$return = [];
		foreach ($rows as $row) {
			$return[] = (string) $row[0];
		}
		return $return;
	}

}
