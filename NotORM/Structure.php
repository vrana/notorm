<?php
namespace NotORM;

/** Information about tables and columns structure
*/
interface Structure {

	/** Get primary key of a table in $db->$table()
	* @param string $table
	* @return string
	*/
	function getPrimary($table);

	/** Get column holding foreign key in $table[$id]->$name()
	* @param string $name
	* @param string $table
	* @return string
	*/
	function getReferencingColumn($name, $table);

	/** Get target table in $table[$id]->$name()
	* @param string $name
	* @param string $table
	* @return string
	*/
	function getReferencingTable($name, $table);

	/** Get column holding foreign key in $table[$id]->$name
	* @param string $name
	* @param string $table
	* @return string
	*/
	function getReferencedColumn($name, $table);

	/** Get table holding foreign key in $table[$id]->$name
	* @param string $name
	* @param string $table
	* @return string
	*/
	function getReferencedTable($name, $table);

	/** Get sequence name, used by insert
	* @param string $table
	* @return string|null|false null if the name is not needed, false to not get the inserted ID
	*/
	function getSequence($table);

}
