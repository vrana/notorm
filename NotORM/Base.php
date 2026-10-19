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
	/** @var string|non-empty-list<string> */ protected string|array $primary;
	/** @var array<Row>|null */ protected ?array $rows = null;
	protected mixed $id = null; // primary key of Row, [$column => $value] for a multi-column primary key
	/** @var array<string, Result|array{}> */ protected array $referenced = [];

	/** @var bool|callable(string, mixed[]): mixed */ protected $debug = false;
	/** @var (callable(): mixed)|null */ protected $debugTimer = null;
	protected bool $freeze = false;
	/** @var class-string<Row> */ protected string $rowClass = Row::class;
	protected bool $jsonAsArray = false;

	/** @return bool|void Result returns bool, Row nothing */
	protected function access(string|int|null $key, bool $delete = false) {
	}

	/** Get primary keys of rows in Result
	* @return list<int|string> distinct values, not the keys of $rows which can be a list
	*/
	protected function ids(): array {
		$return = [];
		foreach ($this->rows ?? [] as $row) {
			if ($row->id !== null) {
				$return[$row->id] = null;
			}
		}
		return array_keys($return);
	}

}
