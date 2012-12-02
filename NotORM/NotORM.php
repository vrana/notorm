<?php
/** NotORM - simple reading data from the database
* @link http://www.notorm.com/
* @author Jakub Vrana, http://www.vrana.cz/
* @copyright 2010 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/

/** Database representation
* @property-write mixed $debug = false Enable debugging queries, true for fwrite(STDERR, $query), callback($query, $parameters) otherwise
* @property-write bool $freeze = false Disable persistence
* @property-write string $rowClass = 'NotORM_Row' Class used for created objects
* @property-write string $transaction Assign 'BEGIN', 'COMMIT' or 'ROLLBACK' to start or stop transaction
*/
namespace NotORM;
use \PDO,
    NotORM\Structure\StructureInterface,
    NotORM\Cache\CacheInterface,
    NotORM\Structure\Convention;
    
class NotORM extends NotORMAbstract {
	
    /** Create database representation
    * @param \PDO
    * @param \NotORM\Structure\StructureInterface or null for new NotORM_Structure_Convention
    * @param \NotORM\Cache\CacheInterface or null for no cache
    */
    public function __construct(PDO $connection, StructureInterface $structure = null, CacheInterface $cache = null) 
    {
        $this->connection = $connection;
        $this->driver = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if (!isset($structure)) {
            $structure = new Convention;
        }
        $this->structure = $structure;
        $this->cache = $cache;
    }

    /** Get table data to use as $db->table[1]
    * @param string
    * @return \NotORM\Result
    */
    public function __get($table) 
    {
        return new Result($this->structure->getReferencingTable($table, ''), $this, true);
    }
    /**
     * 
     * @param \NotORM\Cache\CacheInterface $cache
     * @return \NotORM\NotORM
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }                

    public function getCache()
    {
        return $this->cache;
    }
    /** Set write-only properties
    * @return null
    */
    public function __set($name, $value) 
    {
        if ($name == "debug" || $name == "freeze" || $name == "rowClass") {
            $this->$name = $value;
        }
        if ($name == "transaction") {
            switch (strtoupper($value)) {
                case "BEGIN": return $this->connection->beginTransaction();
                case "COMMIT": return $this->connection->commit();
                case "ROLLBACK": return $this->connection->rollback();
            }
        }
    }

    /** Get table data
    * @param string
    * @param array (["condition"[, array("value")]]) passed to NotORM_Result::where()
    * @return \NotORM\Result
    */
    public function __call($table, array $where) 
    {
        $return = new Result($this->structure->getReferencingTable($table, ''), $this);
        if ($where) {
            call_user_func_array(array($return, 'where'), $where);
        }
        return $return;
    }
	
}
