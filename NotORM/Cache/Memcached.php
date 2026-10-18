<?php declare(strict_types=1);

namespace NotORM\Cache;

/** Cache using "NotORM." prefix in Memcached */
class Memcached implements \NotORM\Cache {
	function __construct(
		private \Memcached $memcached,
	) {
	}

	function load(string $key): mixed {
		$return = $this->memcached->get($this->key($key));
		if ($this->memcached->getResultCode() !== \Memcached::RES_SUCCESS) { // get() returns false also for a missing key
			return null;
		}
		return $return;
	}

	function save(string $key, mixed $data): void {
		$this->memcached->set($this->key($key), $data);
	}

	/** Memcached keys can't contain spaces and are limited to 250 bytes */
	private function key(string $key): string {
		return "NotORM." . md5($key);
	}

}
