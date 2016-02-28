<?php

namespace NotORM;

/**
 * Cache using $_SESSION["NotORM"]
 */
class CacheSession implements CacheInterface
{

	/**
	 * @inheritdoc
	 * @param string $key
	 * @return null
	 */
	public function load($key)
	{
		if (!isset($_SESSION["NotORM"][$key])) {
			return null;
		}
		return $_SESSION["NotORM"][$key];
	}

	/**
	 * @inheritdoc
	 * @param string $key
	 * @param mixed $data
	 * @return null|void
	 */
	public function save($key, $data)
	{
		$_SESSION["NotORM"][$key] = $data;
	}

}