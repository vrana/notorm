<?php
/** NotORM - simple reading data from the database
* @link http://www.notorm.com/
* @author Jakub Vrana, http://www.vrana.cz/
* @copyright 2010 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/
namespace NotORM;

/**
 * Class Instance
 * @package NotORM
 */
class Instance extends AbstractClass {

	/** Create database representation
	 * @param \PDO $connection
	 * @param StructureInterface $structure
	 * @param CacheInterface $cache
	 */
	public function __construct(\PDO $connection, StructureInterface $structure = null, CacheInterface $cache = null) {
		$this->connection = $connection;
		$this->driver = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
		if (!isset($structure)) {
			$structure = new StructureConvention;
		}
		$this->structure = $structure;
		$this->cache = $cache;
	}
	
	/** Get table data to use as $db->table[1]
	* @param string $table
	* @return Result
	*/
	public function __get($table) {
		return new Result($this->structure->getReferencingTable($table, ''), $this, true);
	}

	/** Set write-only properties
	 * @param $name
	 * @param $value
	 * @return null|boolean
	 */
	public function __set($name, $value) {
		if ($name == "debug" || $name == "debugTimer" || $name == "freeze" || $name == "rowClass" || $name == "jsonAsArray") {
			$this->$name = $value;
		} elseif ($name == "transaction") {
			switch (strtoupper($value)) {
				case "BEGIN": return $this->connection->beginTransaction();
				case "COMMIT": return $this->connection->commit();
				case "ROLLBACK": return $this->connection->rollback();
			}
		}
	}
	
	/** Get table data
	* @param string $table
	* @param array $where (["condition"[, array("value")]]) passed to Result::where()
	* @return Result
	*/
	public function __call($table, array $where) {
		if (is_string($table)) {
			$return = new Result($this->structure->getReferencingTable($table, ''), $this);
		} else throw  new \InvalidArgumentException;

		if ($where) {
			call_user_func_array(array($return, 'where'), $where);
		}
		return $return;
	}

	protected function access($key, $delete = false)
	{
		// TODO: Implement access() method. Or not implement at all.
	}
}
