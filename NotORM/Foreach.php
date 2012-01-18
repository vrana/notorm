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
	* @return null
	*/
	function __invoke($result) {
		$callback = $this->callback;
		foreach ($result as $row) {
			$callback($row);
		}
	}
	
}
