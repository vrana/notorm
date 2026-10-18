<?php declare(strict_types=1);

namespace NotORM\Structure\Discovery;

/** Keys from SQLite pragmas */
class SQLite extends Driver {

	function primaryColumns(string $table): array {
		$return = array();
		foreach ($this->rows("PRAGMA table_info(" . $this->connection->quote($table) . ")") as $column) {
			if ($column[5]) { // 5 - position in the primary key
				$return[$column[5]] = $column[1];
			}
		}
		ksort($return);
		return array_values($return);
	}

	function referencingColumns(string $table, string $primary): array {
		$return = array();
		foreach ($this->rows("SELECT name FROM sqlite_master WHERE type = 'table'") as $referencing) {
			foreach ($this->rows("PRAGMA foreign_key_list(" . $this->connection->quote($referencing[0]) . ")") as $foreign) {
				// 2 - table, 3 - from, 4 - to which is NULL for the primary key
				if (strtolower($foreign[2]) == strtolower($table) && ($foreign[4] === null || $foreign[4] == $primary)) {
					$return[] = array($referencing[0], $foreign[3]);
				}
			}
		}
		return $return;
	}

	function referencedTables(string $table): array {
		$return = array();
		foreach ($this->rows("PRAGMA foreign_key_list(" . $this->connection->quote($table) . ")") as $foreign) {
			$return[] = array($foreign[3], $foreign[2]); // 3 - from, 2 - table
		}
		return $return;
	}

}
