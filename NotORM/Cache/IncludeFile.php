<?php
namespace NotORM\Cache;

/** Cache using PHP include */
class IncludeFile implements \NotORM\Cache {
	private string $filename;
	private array $data = array();

	function __construct(string $filename) {
		$this->filename = $filename;
		$data = @include realpath($filename); // @ - file may not exist, realpath() to not include from include_path //! silently falls with syntax error and fails with unreadable file
		$this->data = (is_array($data) ? $data : array()); // empty file returns 1
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
			file_put_contents($this->filename, '<?php return ' . var_export($this->data, true) . ';', LOCK_EX);
		}
	}

}
