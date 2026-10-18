<?php
namespace NotORM\Structure\Discovery;

/** Keys from MS SQL sys tables */
class MSSQL extends Driver {

	function primaryColumns($table) {
		return $this->column($this->rows("
			SELECT c.name
			FROM sys.indexes i
			JOIN sys.index_columns ic ON ic.object_id = i.object_id AND ic.index_id = i.index_id
			JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
			WHERE i.is_primary_key = 1 AND i.object_id = OBJECT_ID(?)
			ORDER BY ic.key_ordinal
		", $table));
	}

	function referencingColumns($table, $primary) {
		return $this->rows("
			SELECT OBJECT_NAME(parent_object_id), COL_NAME(parent_object_id, parent_column_id)
			FROM sys.foreign_key_columns
			WHERE referenced_object_id = OBJECT_ID(?)
		", $table); //! may not reference primary key
	}

	function referencedTables($table) {
		return $this->rows("
			SELECT COL_NAME(parent_object_id, parent_column_id), OBJECT_NAME(referenced_object_id)
			FROM sys.foreign_key_columns
			WHERE parent_object_id = OBJECT_ID(?)
		", $table);
	}

}
