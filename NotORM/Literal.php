<?php declare(strict_types=1);

namespace NotORM;

/** SQL literal value */
class Literal {
	protected string $value = '';

	/** @var list<mixed> */ public array $parameters = [];

	/** Create literal value
	* @param string $value SQL code, can contain ?
	* @param mixed ...$parameters values for ?
	*/
	function __construct(string $value, mixed ...$parameters) {
		$this->value = $value;
		$this->parameters = $parameters;
	}

	/** Get literal value */
	function __toString(): string {
		return $this->value;
	}

}
