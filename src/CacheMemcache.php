<?php

namespace NotORM;

/** Cache using "NotORM." prefix in Memcache
 */
class CacheMemcache implements CacheInterface
{
	private $memcache;

	function __construct(Memcache $memcache)
	{
		$this->memcache = $memcache;
	}

	function load($key)
	{
		$return = $this->memcache->get("NotORM.$key");
		if ($return === false) {
			return null;
		}
		return $return;
	}

	function save($key, $data)
	{
		$this->memcache->set("NotORM.$key", $data);
	}

}