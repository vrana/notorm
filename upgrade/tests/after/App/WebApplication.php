<?php
namespace App;

class WebApplication extends ApplicationRow {

	function offsetExists($key): bool {
		return ($key == "web" || parent::offsetExists($key));
	}

	function __toString(): string {
		return "web " . parent::__toString();
	}

}
