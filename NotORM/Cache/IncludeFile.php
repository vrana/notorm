<?php declare(strict_types=1);

namespace NotORM\Cache;

/** Cache using PHP include */
class IncludeFile implements \NotORM\Cache {
	private string $filename;
	private array $data;

	function __construct(string $filename) {
		$this->filename = $filename;
		$path = realpath($filename); // realpath() to not include from include_path, false if the file doesn't exist
		$data = ($path !== false ? @include $path : []); // @ - file may not be readable //! silently falls with syntax error and fails with unreadable file
		$this->data = (is_array($data) ? $data : []); // empty file returns 1
	}

	function load(string $key): mixed {
		return $this->data[$key] ?? null;
	}

	function save(string $key, mixed $data): void {
		if (($this->data[$key] ?? null) !== $data) {
			$this->data[$key] = $data;
			file_put_contents($this->filename, '<?php return ' . var_export($this->data, true) . ';', LOCK_EX);
		}
	}

}
