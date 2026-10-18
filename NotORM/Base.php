<?php
namespace NotORM;

// friend visibility emulation
abstract class Base {
	protected \PDO $connection;
	protected string $driver;
	protected Structure $structure;
	protected ?Cache $cache;

	protected NotORM $notORM;
	protected string $table;
	protected string $primary;
	protected ?array $rows = null;
	protected array $referenced = array();

	/** @var bool|callable */ protected $debug = false;
	/** @var callable|null */ protected $debugTimer = null;
	protected bool $freeze = false;
	/** @var class-string<Row> */ protected string $rowClass = Row::class;
	protected bool $jsonAsArray = false;

	/** @return bool|void Result returns bool, Row nothing */
	protected function access(?string $key, bool $delete = false) {
	}

}
