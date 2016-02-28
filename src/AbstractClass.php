<?php
namespace NotORM;

abstract class AbstractClass {
	protected $connection, $driver, $structure, $cache;
	protected $notORM, $table, $primary, $rows, $referenced = array();

	protected $debug = false;
	protected $debugTimer;
	protected $freeze = false;
	protected $rowClass = 'NotORM\Row';
	protected $jsonAsArray = false;

	protected abstract function access($key, $delete = false);

}