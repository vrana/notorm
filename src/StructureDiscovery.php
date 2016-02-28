<?php
namespace NotORM;

/**
 * Structure reading meta-informations from the database
 */
class StructureDiscovery implements StructureInterface
{
	protected $connection, $cache, $structure = array();
	protected $foreign;

	/**
	 * Create autodisovery structure
	 * Param use $string "%s_id" to access $name . "_id" column in $row->$name
	 * @param \PDO $connection
	 * @param CacheInterface $cache
	 * @param string $foreign
	 */
	public function __construct(\PDO $connection, CacheInterface $cache = null, $foreign = '%s')
	{
		$this->connection = $connection;
		$this->cache = $cache;
		$this->foreign = $foreign;
		if ($cache) {
			$this->structure = $cache->load("structure");
		}
	}

	/**
	 * Save data to cache
	 */
	public function __destruct()
	{
		if ($this->cache) {
			$this->cache->save("structure", $this->structure);
		}
	}

	/**
	 * @inheritdoc
	 * @param string $name
	 * @param string $table
	 * @return mixed
	 */
	public function getReferencingColumn($name, $table)
	{
		$name = strtolower($name);
		$return = &$this->structure["referencing"][$table];
		if (!isset($return[$name])) {
			foreach ($this->connection->query("
				SELECT TABLE_NAME, COLUMN_NAME
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_NAME = " . $this->connection->quote($table) . "
				AND REFERENCED_COLUMN_NAME = " . $this->connection->quote($this->getPrimary($table)) //! may not reference primary key
			) as $row) {
				$return[strtolower($row[0])] = $row[1];
			}
		}
		return $return[$name];
	}

	/**
	 * @inheritdoc
	 * @param string $table
	 * @return string
	 */
	public function getPrimary($table)
	{
		$return = &$this->structure["primary"][$table];
		if (!isset($return)) {
			$return = "";
			foreach ($this->connection->query("EXPLAIN $table") as $column) {
				if ($column[3] == "PRI") { // 3 - "Key" is not compatible with \PDO::CASE_LOWER
					if ($return != "") {
						$return = ""; // multi-column primary key is not supported
						break;
					}
					$return = $column[0];
				}
			}
		}
		return $return;
	}

	/**
	 * @inheritdoc
	 * @param string $name
	 * @param string $table
	 * @return string
	 */
	public function getReferencingTable($name, $table)
	{
		return $name;
	}

	/**
	 * @inheritdoc
	 * @param string $name
	 * @param string $table
	 * @return mixed
	 */
	public function getReferencedTable($name, $table)
	{
		$column = strtolower($this->getReferencedColumn($name, $table));
		$return = &$this->structure["referenced"][$table];
		if (!isset($return[$column])) {
			foreach ($this->connection->query("
				SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_SCHEMA = DATABASE()
				AND TABLE_NAME = " . $this->connection->quote($table) . "
			") as $row) {
				$return[strtolower($row[0])] = $row[1];
			}
		}
		return $return[$column];
	}

	/**
	 * @inheritdoc
	 * @param string $name
	 * @param string $table
	 * @return string
	 */
	public function getReferencedColumn($name, $table)
	{
		return sprintf($this->foreign, $name);
	}

	/**
	 * @inheritdoc
	 * @param string $table
	 * @return null
	 */
	public function getSequence($table)
	{
		//TODO always null
		return null;
	}

}