<?php
/** NotORM - simple reading data from the database
* @link http://www.notorm.com/
* @author Jakub Vrana, http://www.vrana.cz/
* @copyright 2010 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
*/

require_once dirname(__FILE__) . '/Structure.php';
require_once dirname(__FILE__) . '/Cache.php';
require_once dirname(__FILE__) . '/Literal.php';
require_once dirname(__FILE__) . '/Result.php';
require_once dirname(__FILE__) . '/MultiResult.php';
require_once dirname(__FILE__) . '/Row.php';

// friend visibility emulation
abstract class NotORM_Abstract {
	protected $connection, $structure, $cache;
	protected $notORM, $table, $primary, $rows, $referenced = array();

	protected $debug = FALSE;
	protected $freeze = FALSE;
	protected $rowClass = 'NotORM_Row';

	abstract protected function __construct();

	protected function access($key, $delete = FALSE) {
	}

}

/** Database representation
* @property-write mixed $debug = FALSE Enable debuging queries, TRUE for fwrite(STDERR, $query), callback($query, $parameters) otherwise
* @property-write bool $freeze = FALSE Disable persistence
* @property-write string $rowClass = 'NotORM_Row' Class used for created objects
*/
class NotORM extends NotORM_Abstract {

	/** Create database representation
	* @param PDO
	* @param NotORM_Structure or NULL for new NotORM_Structure_Convention
	* @param NotORM_Cache or NULL for no cache
	*/
	function __construct(PDO $connection, NotORM_Structure $structure = NULL, NotORM_Cache $cache = NULL) {
		$this->connection = $connection;
		if ($structure === NULL) {
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
		return new NotORM_Result($table, $this, TRUE);
	}

	/** Set write-only properties
	* @return NULL
	*/
	function __set($name, $value) {
		if ($name === 'debug' || $name === 'freeze' || $name === 'rowClass') {
			$this->$name = $value;
		}
	}

	/** Get table data
	* @param string
	* @param array (['condition'[, array('value')]]) passed to NotORM_Result::where() or (array|Traversable) passed to NotORM_Result::insert()
	* @return NotORM_Result
	*/
	function __call($table, array $where) {
		$return = new NotORM_Result($table, $this);
		if ($where) {
			call_user_func_array(array($return, 'where'), $where);
		}
		return $return;
	}

}
