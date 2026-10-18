<?php declare(strict_types=1);

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
	/** @var array<Row>|null */ protected ?array $rows = null;
	/** @var array<string, Result|array{}> */ protected array $referenced = [];

	/** @var bool|callable */ protected $debug = false;
	/** @var callable|null */ protected $debugTimer = null;
	protected bool $freeze = false;
	/** @var class-string<Row> */ protected string $rowClass = Row::class;
	protected bool $jsonAsArray = false;

	/** @return bool|void Result returns bool, Row nothing */
	protected function access(string|int|null $key, bool $delete = false) {
	}

}
