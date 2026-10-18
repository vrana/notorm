<?php
namespace NotORM;

// friend visibility emulation
abstract class Base {
	protected $connection;
	protected $driver;
	protected $structure;
	protected $cache;

	protected $notORM;
	protected $table;
	protected $primary;
	protected $rows;
	protected $referenced = array();

	protected $debug = false;
	protected $debugTimer;
	protected $freeze = false;
	/** @var class-string<Row> */ protected $rowClass = Row::class;
	protected $jsonAsArray = false;

	protected function access($key, $delete = false) {
	}

}
