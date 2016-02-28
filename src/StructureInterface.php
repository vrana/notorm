<?php
namespace NotORM;

 /**
 * Information about tables and columns structure
 */
interface StructureInterface {

	/**
	 * Get primary key of a table in $db->$table()
	 * @param string $table
	 * @return string
	 */
	public function getPrimary($table);

	/**
	 * Get column holding foreign key in $table[$id]->$name()
	 * @param string $name
	 * @param string $table
	 * @return string
	 */
	public function getReferencingColumn($name, $table);

	/**
	 * Get target table in $table[$id]->$name()
	 * @param string $name
	 * @param string $table
	 * @return string
	 */
	public function getReferencingTable($name, $table);

	/**
	 * Get column holding foreign key in $table[$id]->$name
	 * @param string $name
	 * @param string $table
	 * @return string
	 */
	public function getReferencedColumn($name, $table);

	/**
	 * Get table holding foreign key in $table[$id]->$name
	 * @param string $name
	 * @param string $table
	 * @return string
	 */
	public function getReferencedTable($name, $table);

	/**
	 * Get sequence name, used by insert
	 * @param string $table
	 * @return string
	 */
	public function getSequence($table);

}
