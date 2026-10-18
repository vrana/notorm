<?php declare(strict_types=1);

namespace NotORM\Cache;

/** Cache using file */
class File implements \NotORM\Cache {
	/** @var array<string, mixed> */ private array $data;

	function __construct(
		private string $filename,
	) {
		$data = unserialize((string) @file_get_contents($filename)); // @ - file may not exist
		$this->data = (is_array($data) ? $data : []); // the file doesn't exist or is not readable
	}

	function load(string $key): mixed {
		return $this->data[$key] ?? null;
	}

	function save(string $key, mixed $data): void {
		if (($this->data[$key] ?? null) !== $data) {
			$this->data[$key] = $data;
			file_put_contents($this->filename, serialize($this->data), LOCK_EX);
		}
	}

}
