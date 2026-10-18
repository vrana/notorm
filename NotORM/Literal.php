<?php
namespace NotORM;

/** SQL literal value
*/
class Literal {
	protected $value = '';

	/** @var list<mixed> */ public $parameters = array();

	/** Create literal value
	* @param string $value SQL code, can contain ?
	* @param mixed ...$parameters values for ?
	*/
	function __construct($value, ...$parameters) {
		$this->value = $value;
		$this->parameters = $parameters;
	}

	/** Get literal value
	* @return string
	*/
	function __toString() {
		return $this->value;
	}

}
