<?php
/** NotORM - simple reading data from the database
* @link http://www.notorm.com/
* @author Jakub Vrana, http://www.vrana.cz/
* @copyright 2010 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
*/

include dirname(__FILE__) . "/NotORM/Structure.php";
include dirname(__FILE__) . "/NotORM/Cache.php";
include dirname(__FILE__) . "/NotORM/Result.php";
include dirname(__FILE__) . "/NotORM/MultiResult.php";
include dirname(__FILE__) . "/NotORM/Row.php";

// friend visibility emulation
abstract class NotORM_Abstract {
	protected $connection, $structure, $cache;
	protected $notORM, $table, $primary, $rows, $referenced = array(), $freeze = false;
	
	protected function access($key) {
	}
	
}

/** Database representation
*/
class NotORM extends NotORM_Abstract {
	
	/** Create database representation
	* @param PDO
	* @param NotORM_Structure or null for new NotORM_Structure_Convention
	* @param NotORM_Cache or null for no cache
	*/
	function __construct(PDO $connection, NotORM_Structure $structure = null, NotORM_Cache $cache = null) {
		$this->connection = $connection;
		if (!isset($structure)) {
			$structure = new NotORM_Structure_Convention;
		}
		$this->structure = $structure;
		$this->cache = $cache;
	}
	
	/** Get table data to use as $db->table[1]
	* @param string
	* @return NotORM_Result
	*/
	function __get($table) {
		return new NotORM_Result($table, $this, true);
	}
	
	// __set is not defined to allow storing custom result sets (undocumented)
	
	/** Get table data
	* @param string
	* @param array (["condition"[, array("value")]]) passed to NotORM_Result::where() or (array|Traversable) passed to NotORM_Result::insert()
	* @return NotORM_Result
	*/
	function __call($table, array $where) {
		$return = new NotORM_Result($table, $this);
		if ($where) {
			$data = $where[0];
			if ($data instanceof Traversable) {
				$data = iterator_to_array($data); // insert() accepts only arrays
			}
			if (is_array($data)) { // $db->$table($array) is a shortcut for $db->$table()->insert($array)
				return $return->insert($data);
			}
			call_user_func_array(array($return, 'where'), $where);
		}
		return $return;
	}
	
}
