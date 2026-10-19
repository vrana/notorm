<?php

class SoftwareConvention extends \NotORM\Structure\Convention {
	function getReferencedTable($name, $table): string {
		switch ($name) {
			case 'maintainer': return parent::getReferencedTable('author', $table);
		}
		return parent::getReferencedTable($name, $table);
	}
}

class ArrayCache implements \NotORM\Cache {
	private $data = array();

	function load($key): mixed {
		return (isset($this->data[$key]) ? $this->data[$key] : null);
	}

	function save($key, $data): void {
		$this->data[$key] = $data;
	}
}

class Software extends \NotORM\NotORM {
	protected string $rowClass = 'App\ApplicationRow';
}
