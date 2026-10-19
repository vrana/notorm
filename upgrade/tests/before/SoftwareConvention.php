<?php

class SoftwareConvention extends NotORM_Structure_Convention {
	function getReferencedTable($name, $table) {
		switch ($name) {
			case 'maintainer': return parent::getReferencedTable('author', $table);
		}
		return parent::getReferencedTable($name, $table);
	}
}

class ArrayCache implements NotORM_Cache {
	private $data = array();

	function load($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : null);
	}

	function save($key, $data) {
		$this->data[$key] = $data;
	}
}

class Software extends NotORM {
	protected $rowClass = 'App\ApplicationRow';
}
