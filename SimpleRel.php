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
	* @param string table
	* @param array (["condition"[, array("value")]])
	* @return SimpleRel_Result
	*/
	function __call($name, array $args) {
		$return = new SimpleRel_Result($name, $this, $this->pdo, $this->structure);
		if ($args) {
			call_user_func_array(array($return, 'where'), $args);
		}
		return $return;
	}
}
