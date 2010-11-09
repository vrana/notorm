<?php

/** SQL literal value
*/
class NotORM_Literal {
	/** @var string */
	protected $value = '';
	
	/** Create literal value
	* @param string
	*/
	function __construct($value) {
		$this->value = $value;
	}
	
	/** Get literal value
	* @return string
	*/
	function __toString() {
		return $this->value;
	}
	
}
