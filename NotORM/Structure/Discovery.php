<?php declare(strict_types=1);

namespace NotORM\Structure;

/** Structure reading meta-informations from the database */
class Discovery implements \NotORM\Structure {
	/** @var array<string, class-string<Discovery\Driver>> */ protected static array $drivers = [ // PDO driver name => class
		"mysql" => Discovery\MySQL::class,
		"sqlite" => Discovery\SQLite::class,
		"pgsql" => Discovery\PostgreSQL::class,
		"sqlsrv" => Discovery\MSSQL::class,
		"dblib" => Discovery\MSSQL::class,
		"mssql" => Discovery\MSSQL::class,
		"oci" => Discovery\Oracle::class,
	];
	protected Discovery\Driver $driver;
	/** @var array<string, array<string, mixed>> */ protected array $structure;
	/** Create autodisovery structure
	* @param string $foreign use "%s_id" to access $name . "_id" column in $row->$name
	*/
	function __construct(
		\PDO $connection,
		protected ?\NotORM\Cache $cache = null,
		protected string $foreign = '%s',
	) {
		$name = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
		$class = self::$drivers[$name] ?? throw new \InvalidArgumentException("Discovery doesn't support the PDO driver $name.");
		$this->driver = new $class($connection);
		$this->structure = $cache?->load("structure") ?? [];
	}

	/** Save data to cache */
	function __destruct() {
		$this->cache?->save("structure", $this->structure);
	}

	function getPrimary(string $table): string {
		$return = &$this->structure["primary"][$table];
		if (!isset($return)) {
			$columns = $this->driver->primaryColumns($table);
			$return = (count($columns) == 1 ? $columns[0] : ""); // multi-column primary key is not supported
		}
		return $return;
	}

	function getReferencingColumn(string $name, string $table): string {
		$name = strtolower($name);
		$return = &$this->structure["referencing"][$table];
		if (!isset($return[$name])) {
			foreach ($this->driver->referencingColumns($table, $this->getPrimary($table)) as $row) {
				$return[strtolower($row[0])] = $row[1];
			}
		}
		return $return[$name];
	}

	function getReferencingTable(string $name, string $table): string {
		return $name;
	}

	function getReferencedColumn(string $name, string $table): string {
		return sprintf($this->foreign, $name);
	}

	function getReferencedTable(string $name, string $table): string {
		$column = strtolower($this->getReferencedColumn($name, $table));
		$return = &$this->structure["referenced"][$table];
		if (!isset($return[$column])) {
			foreach ($this->driver->referencedTables($table) as $row) {
				$return[strtolower($row[0])] = $row[1];
			}
		}
		return $return[$column];
	}

	function getSequence(string $table): string|null|false {
		$this->structure["sequence"][$table] ??= $this->driver->sequence($table, $this->getPrimary($table));
		return $this->structure["sequence"][$table];
	}

}
