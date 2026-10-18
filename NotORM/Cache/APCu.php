<?php
namespace NotORM\Cache;

/** Cache using "NotORM." prefix in APCu */
class APCu implements \NotORM\Cache {

	function load(string $key): mixed {
		$return = apcu_fetch("NotORM.$key", $success);
		if (!$success) {
			return null;
		}
		return $return;
	}

	function save(string $key, mixed $data): void {
		apcu_store("NotORM.$key", $data);
	}

}
