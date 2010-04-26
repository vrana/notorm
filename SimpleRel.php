<?php
include dirname(__FILE__) . "/SimpleRel/Structure.php";
include dirname(__FILE__) . "/SimpleRel/Result.php";
include dirname(__FILE__) . "/SimpleRel/Row.php";

class SimpleRel {
	private $pdo, $structure;
	
	/** Create database representation
	* @param PDO
	* @param SimpleRel_Structure
	*/
	function __construct(PDO $pdo, SimpleRel_Structure $structure = null) {
		$this->pdo = $pdo;
		if (!isset($structure)) {
			$structure = new SimpleRel_Structure_Convention;
		}
		$this->structure = $structure;
	}
	
	/** Get table data
	* @param string
	* @return SimpleRel_Result
	*/
	function __get($table) {
		return new SimpleRel_Result($table, $this, $this->pdo, $this->structure);
	}
	
	/** Get table data
	* @param string
	* @param array (["condition"[, array("value")]])
	* @return SimpleRel_Result
	*/
	function __call($table, array $where) {
		$return = new SimpleRel_Result($table, $this, $this->pdo, $this->structure);
		if ($where) {
			call_user_func_array(array($return, 'where'), $where);
		}
		return $return;
	}
}
