<?php

namespace NotORM\Cache;

class Apc implements CacheInterface
{
    public function load($key) {
		$return = apc_fetch("NotORM.$key", $success);
		if (!$success) {
			return null;
		}
		return $return;
	}
	
	function save($key, $data) {
		apc_store("NotORM.$key", $data);
	}
}


