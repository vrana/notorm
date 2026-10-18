<?php
namespace NotORM\Cache;

/** Cache using $_SESSION["NotORM"] */
class Session implements \NotORM\Cache {

	function load(string $key): mixed {
		if (!isset($_SESSION["NotORM"][$key])) {
			return null;
		}
		return $_SESSION["NotORM"][$key];
	}

	function save(string $key, mixed $data): void {
		$_SESSION["NotORM"][$key] = $data;
	}

}
