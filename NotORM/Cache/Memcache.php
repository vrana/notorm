<?php declare(strict_types=1);

namespace NotORM\Cache;

/** Cache using "NotORM." prefix in Memcache */
class Memcache implements \NotORM\Cache {
	function __construct(
		private \Memcache $memcache,
	) {
	}

	function load(string $key): mixed {
		$return = $this->memcache->get("NotORM.$key");
		if ($return === false) {
			return null;
		}
		return $return;
	}

	function save(string $key, mixed $data): void {
		$this->memcache->set("NotORM.$key", $data);
	}

}
