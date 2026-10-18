<?php
namespace NotORM\Structure\Discovery;

/** Keys from PostgreSQL pg_constraint */
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

	function sequence($table, $primary) {
		$rows = $this->rows("
			SELECT pg_get_serial_sequence(CAST(CAST(attrelid AS regclass) AS text), attname)
			FROM pg_attribute
			WHERE attrelid = CAST(? AS regclass) AND attname = ?
		", $table, $primary); // pg_get_serial_sequence() would fail for a non-existing column
		return ($rows && $rows[0][0] !== null ? $rows[0][0] : false); // LASTVAL() would return a value of another sequence or fail and abort the transaction
	}

}
