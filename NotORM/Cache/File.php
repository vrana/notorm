<?php declare(strict_types=1);

namespace NotORM\Cache;

/** Cache using file */
class File implements \NotORM\Cache {
	private string $filename;
	private array $data;

	function __construct(string $filename) {
		$this->filename = $filename;
		$data = unserialize((string) @file_get_contents($filename)); // @ - file may not exist
		$this->data = (is_array($data) ? $data : array()); // the file doesn't exist or is not readable
	}

	function load(string $key): mixed {
		if (!isset($this->data[$key])) {
			return null;
		}
		return $this->data[$key];
	}

	function save(string $key, mixed $data): void {
		if (!isset($this->data[$key]) || $this->data[$key] !== $data) {
			$this->data[$key] = $data;
			file_put_contents($this->filename, serialize($this->data), LOCK_EX);
		}
	}

}
