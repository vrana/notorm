<?php
namespace NotORM;

// friend visibility emulation
abstract class Base {
	protected $connection, $driver, $structure, $cache;
	protected $notORM, $table, $primary, $rows, $referenced = array();

	protected $debug = false;
	protected $debugTimer;
	protected $freeze = false;
	/** @var class-string<Row> */
	protected $rowClass = Row::class;
	protected $jsonAsArray = false;

	protected function access($key, $delete = false) {
	}

}
