<?php declare(strict_types=1);

namespace NotORM\Structure\Discovery;

/** Keys from Oracle all_constraints and identity columns of the current schema, the names are lowercased */
class Oracle extends Driver {

	function sequence(string $table, string $primary): ?string {
		$rows = $this->rows("
			SELECT sequence_name
			FROM all_tab_identity_cols
			WHERE owner = SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA') AND table_name = UPPER(?) AND column_name = UPPER(?)
		", $table, $primary); // since Oracle 12c
		return ($rows ? $rows[0][0] : ''); // e.g. ISEQ$$_80934, its name changes when the table is created again
	}

	function primaryColumns(string $table): array {
		return $this->column($this->rows("
			SELECT LOWER(cc.column_name)
			FROM all_constraints c
			JOIN all_cons_columns cc ON cc.owner = c.owner AND cc.constraint_name = c.constraint_name
			WHERE c.owner = SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA') AND c.constraint_type = 'P' AND c.table_name = UPPER(?)
			ORDER BY cc.position
		", $table));
	}

	function referencingColumns(string $table, string $primary): array {
		return $this->rows("
			SELECT LOWER(c.table_name), LOWER(cc.column_name)
			FROM all_constraints c
			JOIN all_cons_columns cc ON cc.owner = c.owner AND cc.constraint_name = c.constraint_name
			JOIN all_constraints r ON r.owner = c.r_owner AND r.constraint_name = c.r_constraint_name
			WHERE c.owner = SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA') AND c.constraint_type = 'R' AND r.table_name = UPPER(?)
		", $table); //! may not reference primary key
	}

	function referencedTables(string $table): array {
		return $this->rows("
			SELECT LOWER(cc.column_name), LOWER(r.table_name)
			FROM all_constraints c
			JOIN all_cons_columns cc ON cc.owner = c.owner AND cc.constraint_name = c.constraint_name
			JOIN all_constraints r ON r.owner = c.r_owner AND r.constraint_name = c.r_constraint_name
			WHERE c.owner = SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA') AND c.constraint_type = 'R' AND c.table_name = UPPER(?)
		", $table);
	}

}
