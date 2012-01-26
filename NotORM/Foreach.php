<?php

/** NotORM_Result::thenForeach() helper
*/
class NotORM_Foreach {
	protected $callback;
	
	/** Create callback
	* @param callback
	*/
	function __construct($callback) {
		$this->callback = $callback;
	}
	
	/** Call callback for each row
	* @param NotORM_Result
	* @return null
	*/
	function __invoke(NotORM_Result $result) {
		$callback = $this->callback;
		foreach ($result as $id => $row) {
			$callback($row, $id);
		}
	}
	
}
