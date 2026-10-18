<?php
namespace NotORM\Cache;

/** Cache using $_SESSION["NotORM"]
*/
class Session implements \NotORM\Cache {
	
	function load($key) {
		if (!isset($_SESSION["NotORM"][$key])) {
			return null;
		}
		return $_SESSION["NotORM"][$key];
	}
	
	function save($key, $data) {
		$_SESSION["NotORM"][$key] = $data;
	}
	
}
