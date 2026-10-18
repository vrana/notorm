<?php declare(strict_types=1);

namespace NotORM;

/** Information about tables and columns structure */
interface Structure {

	/** Get primary key of a table in $db->$table() */
	function getPrimary(string $table): string;

	/** Get column holding foreign key in $table[$id]->$name() */
	function getReferencingColumn(string $name, string $table): string;

	/** Get target table in $table[$id]->$name() */
	function getReferencingTable(string $name, string $table): string;

	/** Get column holding foreign key in $table[$id]->$name */
	function getReferencedColumn(string $name, string $table): string;

	/** Get table holding foreign key in $table[$id]->$name */
	function getReferencedTable(string $name, string $table): string;

	/** Get sequence name, used by insert
	* @return string|null|false null if the name is not needed, false to not get the inserted ID
	*/
	function getSequence(string $table): string|false|null;

}
