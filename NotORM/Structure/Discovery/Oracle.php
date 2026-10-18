<?php
namespace NotORM\Structure\Discovery;

/** Keys from Oracle user_constraints, the names are lowercased */
class Oracle extends Driver {

	function primaryColumns($table) {
		return $this->column($this->rows("
			SELECT LOWER(cc.column_name)
			FROM user_constraints c
			JOIN user_cons_columns cc ON cc.constraint_name = c.constraint_name
			WHERE c.constraint_type = 'P' AND c.table_name = UPPER(?)
			ORDER BY cc.position
		", $table));
	}

	function referencingColumns($table, $primary) {
		return $this->rows("
			SELECT LOWER(c.table_name), LOWER(cc.column_name)
			FROM user_constraints c
			JOIN user_cons_columns cc ON cc.constraint_name = c.constraint_name
			JOIN user_constraints r ON r.constraint_name = c.r_constraint_name
			WHERE c.constraint_type = 'R' AND r.table_name = UPPER(?)
		", $table); //! may not reference primary key
	}

	function referencedTables($table) {
		return $this->rows("
			SELECT LOWER(cc.column_name), LOWER(r.table_name)
			FROM user_constraints c
			JOIN user_cons_columns cc ON cc.constraint_name = c.constraint_name
			JOIN user_constraints r ON r.constraint_name = c.r_constraint_name
			WHERE c.constraint_type = 'R' AND c.table_name = UPPER(?)
		", $table);
	}

}
