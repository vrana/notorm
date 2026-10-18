<?php declare(strict_types=1);

namespace NotORM;

/** SQL literal value */
class Literal {
	/** @var list<mixed> */ public array $parameters = [];

	/** Create literal value
	* @param string $value SQL code, can contain ?
	* @param mixed ...$parameters values for ?
	*/
	function __construct(
		protected string $value,
		mixed ...$parameters,
	) {
		$this->parameters = $parameters;
	}

	/** Get literal value */
	function __toString(): string {
		return $this->value;
	}

}
