<?php
namespace NotORM;

/** SQL literal value
*/
class Literal {
	protected $value = '';

	/** @var list<mixed> */
	public $parameters = array();

	/** Create literal value
	* @param string $value SQL code, can contain ?
	* @param mixed ...$parameters values for ?
	*/
	function __construct($value) {
		$this->value = $value;
		$this->parameters = func_get_args();
		array_shift($this->parameters);
	}

	/** Get literal value
	* @return string
	*/
	function __toString() {
		return $this->value;
	}

}
