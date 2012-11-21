<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author FELIPE
 */
namespace NotORM\Structure;

interface StructureInterface 
{   	
	/** Get primary key of a table in $db->$table()
	* @param string
	* @return string
	*/
	function getPrimary($table);
	
	/** Get column holding foreign key in $table[$id]->$name()
	* @param string
	* @param string
	* @return string
	*/
	function getReferencingColumn($name, $table);
	
	/** Get target table in $table[$id]->$name()
	* @param string
	* @param string
	* @return string
	*/
	function getReferencingTable($name, $table);
	
	/** Get column holding foreign key in $table[$id]->$name
	* @param string
	* @param string
	* @return string
	*/
	function getReferencedColumn($name, $table);
	
	/** Get table holding foreign key in $table[$id]->$name
	* @param string
	* @param string
	* @return string
	*/
	function getReferencedTable($name, $table);
	
	/** Get sequence name, used by insert
	* @param string
	* @return string
	*/
	function getSequence($table);	

}


