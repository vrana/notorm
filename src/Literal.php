<?php
namespace NotORM;

/** SQL literal value
*/
class Literal {
	protected $value = '';
	
	/** @var array */
	public $parameters = array();
	
	/** Create literal value
	* @param string
	* @param mixed parameter
	* @param mixed ...
	*/
	public function __construct($value) {
		$this->value = $value;
		$this->parameters = func_get_args();
		array_shift($this->parameters);
	}
	
	/** Get literal value
	* @return string
	*/
	public function __toString() {
		return $this->value;
	}
	
}
