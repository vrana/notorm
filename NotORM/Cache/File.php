<?php
namespace NotORM\Cache;

/** Cache using file */
class File implements \NotORM\Cache {
	private $filename;
	private $data;

	function __construct($filename) {
		$this->filename = $filename;
		$this->data = unserialize((string) @file_get_contents($filename)); // @ - file may not exist
		if (!is_array($this->data)) { // the file doesn't exist or is not readable
			$this->data = array();
		}
	}

	function load($key) {
		if (!isset($this->data[$key])) {
			return null;
		}
		return $this->data[$key];
	}

	function save($key, $data) {
		if (!isset($this->data[$key]) || $this->data[$key] !== $data) {
			$this->data[$key] = $data;
			file_put_contents($this->filename, serialize($this->data), LOCK_EX);
		}
	}

}
