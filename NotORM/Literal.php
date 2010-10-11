<?php

/** SQL literal value
*/
class NotORM_Literal {
	/** @var string */
	public $value = '';
	
	/** Create literal value
	* @param string
	*/
	function __construct($value) {
		$this->value = $value;
	}
}
