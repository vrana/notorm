<?php
namespace NotORM\Cache;

/** Cache using "NotORM." prefix in Memcache */
class Memcache implements \NotORM\Cache {
	private $memcache;

	function __construct(\Memcache $memcache) {
		$this->memcache = $memcache;
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
