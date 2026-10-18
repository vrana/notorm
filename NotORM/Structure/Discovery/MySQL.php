<?php
namespace NotORM\Structure\Discovery;

/** Keys from MySQL information_schema */
class MySQL extends Driver {

	function primaryColumns(string $table): array {
		$return = array();
		foreach ($this->connection->query("EXPLAIN $table", \PDO::FETCH_NUM) as $column) {
			if ($column[3] == "PRI") { // 3 - "Key" is not compatible with PDO::CASE_LOWER
				$return[] = $column[0];
			}
		}
		return $return;
	}

	function referencingColumns(string $table, string $primary): array {
		return $this->rows("
			SELECT TABLE_NAME, COLUMN_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = DATABASE()
			AND REFERENCED_TABLE_SCHEMA = DATABASE()
			AND REFERENCED_TABLE_NAME = ?
			AND REFERENCED_COLUMN_NAME = ?
		", $table, $primary);
	}

	function referencedTables(string $table): array {
		return $this->rows("
			SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = DATABASE()
			AND REFERENCED_TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = ?
		", $table);
	}

}
