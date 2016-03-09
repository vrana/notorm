<?php

namespace NotORM;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;

/**
 * Cache using Stash
 * @link http://www.stashphp.com/
 * @link https://github.com/tedious/stash/
 */

class Cache implements CacheInterface
{
	private $pool;

	/**
	 * Cache constructor.
	 * @param DriverInterface $driver
	 */
	public function __construct(DriverInterface $driver = null)
	{
		$this->pool = new Pool($driver);
	}

	/**
	 * @inheritdoc
	 * @param string $key
	 * @return null
	 */
	public function load($key)
	{
		$item = $this->pool->getItem('NotORM/' . $key);
		return isset($item) ? $item->get() : null;
	}

	/**
	 * @inheritdoc
	 * @param string $key
	 * @param mixed $data
	 * @return null|void
	 */
	public function save($key, $data)
	{
		$item = $this->pool->getItem('NotORM/' . $key);
		$item->set($data);
		$this->pool->save($item);
	}

}