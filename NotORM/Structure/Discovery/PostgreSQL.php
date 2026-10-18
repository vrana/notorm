<?php
namespace NotORM\Structure\Discovery;

/** Keys from PostgreSQL pg_constraint
*/
class PostgreSQL extends Driver {

	function primaryColumns($table) {
		return $this->column($this->rows("
			SELECT a.attname
			FROM pg_constraint c
			JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
			WHERE c.conrelid = CAST(? AS regclass) AND c.contype = 'p'
		", $table));
	}

	function referencingColumns($table, $primary) {
		return $this->rows("
			SELECT CAST(c.conrelid AS regclass), a.attname
			FROM pg_constraint c
			JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
			WHERE c.confrelid = CAST(? AS regclass) AND c.contype = 'f'
		", $table); //! may not reference primary key
	}

	function referencedTables($table) {
		return $this->rows("
			SELECT a.attname, CAST(c.confrelid AS regclass)
			FROM pg_constraint c
			JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
			WHERE c.conrelid = CAST(? AS regclass) AND c.contype = 'f'
		", $table);
	}

}
