<?php
namespace App;

class WebApplication extends ApplicationRow {

	function offsetExists($key) {
		return ($key == "web" || parent::offsetExists($key));
	}

	function __toString() {
		return "web " . parent::__toString();
	}

}
