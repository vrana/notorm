<?php
/** NotORM - simple reading data from the database
* @link http://www.notorm.com/
* @author Jakub Vrana, http://www.vrana.cz/
* @copyright 2010 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
*/

include dirname(__FILE__) . "/NotORM/Structure.php";
include dirname(__FILE__) . "/NotORM/Result.php";
include dirname(__FILE__) . "/NotORM/MultiResult.php";
include dirname(__FILE__) . "/NotORM/Row.php";

/** Database representation
*/
class NotORM {
	private $pdo, $structure;
	
	/** Create database representation
	* @param PDO
	* @param NotORM_Structure or null for new NotORM_Structure_Convention
	*/
	function __construct(PDO $pdo, NotORM_Structure $structure = null) {
		$this->pdo = $pdo;
		if (!isset($structure)) {
			$structure = new NotORM_Structure_Convention;
		}
		$this->structure = $structure;
	}
	
	/** Get table data to use as $db->table[1]
	* @param string
	* @return NotORM_Result
	*/
	function __get($table) {
		return new NotORM_Result($table, $this->pdo, $this->structure, true);
	}
	
	/** Get table data
	* @param string
	* @param array (["condition"[, array("value")]]) passed to NotORM_Result::where()
	* @return NotORM_Result
	*/
	function __call($table, array $where) {
		$return = new NotORM_Result($table, $this->pdo, $this->structure);
		if ($where) {
			call_user_func_array(array($return, 'where'), $where);
		}
		return $return;
	}
	
}
