<?php
/**
 * Created by PhpStorm.
 * User: sim
 * Date: 28.02.16
 * Time: 04:07
 */
namespace NotORM;

/** Cache using file
 */
class CacheFile implements CacheInterface
{
	private $filename, $data = array();

	function __construct($filename)
	{
		$this->filename = $filename;
		$this->data = unserialize(@file_get_contents($filename)); // @ - file may not exist
	}

	function load($key)
	{
		if (!isset($this->data[$key])) {
			return null;
		}
		return $this->data[$key];
	}

	function save($key, $data)
	{
		if (!isset($this->data[$key]) || $this->data[$key] !== $data) {
			$this->data[$key] = $data;
			file_put_contents($this->filename, serialize($this->data), LOCK_EX);
		}
	}

}