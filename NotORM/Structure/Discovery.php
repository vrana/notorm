<?php
namespace NotORM\Structure;

/** Structure reading meta-informations from the database */
class Discovery implements \NotORM\Structure {
	/** @var array<string, class-string<Discovery\Driver>> PDO driver name => class */ protected static $drivers = array(
		"mysql" => Discovery\MySQL::class,
		"sqlite" => Discovery\SQLite::class,
		"pgsql" => Discovery\PostgreSQL::class,
		"sqlsrv" => Discovery\MSSQL::class,
		"dblib" => Discovery\MSSQL::class,
		"mssql" => Discovery\MSSQL::class,
		"oci" => Discovery\Oracle::class,
	);
	protected $driver;
	protected $cache;
	protected $structure = array();
	protected $foreign;

	/** Create autodisovery structure
	* @param \PDO $connection
	* @param \NotORM\Cache|null $cache
	* @param string $foreign use "%s_id" to access $name . "_id" column in $row->$name
	*/
	function __construct(\PDO $connection, ?\NotORM\Cache $cache = null, $foreign = '%s') {
		$name = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
		if (!isset(self::$drivers[$name])) {
			throw new \InvalidArgumentException("Discovery doesn't support the PDO driver $name.");
		}
		$this->driver = new self::$drivers[$name]($connection);
		$this->cache = $cache;
		$this->foreign = $foreign;
		if ($cache) {
			$this->structure = $cache->load("structure");
		}
	}

	/** Save data to cache */
	function __destruct() {
		if ($this->cache) {
			$this->cache->save("structure", $this->structure);
		}
	}

	function getPrimary($table) {
		$return = &$this->structure["primary"][$table];
		if (!isset($return)) {
			$columns = $this->driver->primaryColumns($table);
			$return = (count($columns) == 1 ? $columns[0] : ""); // multi-column primary key is not supported
		}
		return $return;
	}

	function getReferencingColumn($name, $table) {
		$name = strtolower($name);
		$return = &$this->structure["referencing"][$table];
		if (!isset($return[$name])) {
			foreach ($this->driver->referencingColumns($table, $this->getPrimary($table)) as $row) {
				$return[strtolower($row[0])] = $row[1];
			}
		}
		return $return[$name];
	}

	function getReferencingTable($name, $table) {
		return $name;
	}

	function getReferencedColumn($name, $table) {
		return sprintf($this->foreign, $name);
	}

	function getReferencedTable($name, $table) {
		$column = strtolower($this->getReferencedColumn($name, $table));
		$return = &$this->structure["referenced"][$table];
		if (!isset($return[$column])) {
			foreach ($this->driver->referencedTables($table) as $row) {
				$return[strtolower($row[0])] = $row[1];
			}
		}
		return $return[$column];
	}

	function getSequence($table) {
		$return = &$this->structure["sequence"][$table];
		if (!isset($return)) {
			$return = $this->driver->sequence($table, $this->getPrimary($table));
		}
		return $return;
	}

}
