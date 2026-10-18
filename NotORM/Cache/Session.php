<?php declare(strict_types=1);

namespace NotORM\Cache;

/** Cache using $_SESSION["NotORM"] */
class Session implements \NotORM\Cache {

	function load(string $key): mixed {
		return $_SESSION["NotORM"][$key] ?? null;
	}

	function save(string $key, mixed $data): void {
		$_SESSION["NotORM"][$key] = $data;
	}

}
