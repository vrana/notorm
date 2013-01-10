<?php

/** Single row representation
*/
class NotORM_Row extends NotORM_Abstract implements IteratorAggregate, ArrayAccess, Countable
{
    private $modified = array();
    protected $row, $result;

    /** @access protected must be public because it is called from Result */
    public function __construct(array $row, NotORM_Result $result)
    {
        $this->row = $row;
        $this->result = $result;
    }

    /** Get primary key value
    * @return string
    */
    public function __toString()
    {
        return (string) $this[$this->result->primary]; // (string) - PostgreSQL returns int
    }

    /** Get referenced row
    * @param string
    * @return NotORM_Row or null if the row does not exist
    */
    public function __get($name)
    {
        $column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
        $referenced = &$this->result->referenced[$name];
        if (!isset($referenced)) {
            $keys = array();
            foreach ($this->result->rows as $row) {
                if ($row[$column] !== null) {
                    $keys[$row[$column]] = null;
                }
            }
            if ($keys) {
                $table = $this->result->notORM->structure->getReferencedTable($name, $this->result->table);
                $referenced = new NotORM_Result($table, $this->result->notORM);
                $referenced->where("$table." . $this->result->notORM->structure->getPrimary($table), array_keys($keys));
            } else {
                $referenced = array();
            }
        }
        if (!isset($referenced[$this[$column]])) { // referenced row may not exist

            return null;
        }

        return $referenced[$this[$column]];
    }

    /** Test if referenced row exists
    * @param string
    * @return bool
    */
    public function __isset($name)
    {
        return ($this->__get($name) !== null);
    }

    /** Store referenced value
    * @param string
    * @param NotORM_Row or null
    * @return null
    */
    public function __set($name, NotORM_Row $value = null)
    {
        $column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
        $this[$column] = $value;
    }

    /** Remove referenced column from data
    * @param string
    * @return null
    */
    public function __unset($name)
    {
        $column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
        unset($this[$column]);
    }

    /** Get referencing rows
    * @param string table name
    * @param array (["condition"[, array("value")]])
    * @return NotORM_MultiResult
    */
    public function __call($name, array $args)
    {
        $table = $this->result->notORM->structure->getReferencingTable($name, $this->result->table);
        $column = $this->result->notORM->structure->getReferencingColumn($table, $this->result->table);
        $return = new NotORM_MultiResult($table, $this->result, $column, $this[$this->result->primary]);
        $return->where("$table.$column", array_keys((array) $this->result->rows)); // (array) - is null after insert
        if ($args) {
            call_user_func_array(array($return, 'where'), $args);
        }

        return $return;
    }

    /** Update row
    * @param array or null for all modified values
    * @return int number of affected rows or false in case of an error
    */
    public function update($data = null)
    {
        // update is an SQL keyword
        if (!isset($data)) {
            $data = $this->modified;
        }
        $result = new NotORM_Result($this->result->table, $this->result->notORM);

        return $result->where($this->result->primary, $this[$this->result->primary])->update($data);
    }

    /** Delete row
    * @return int number of affected rows or false in case of an error
    */
    public function delete()
    {
        // delete is an SQL keyword
        $result = new NotORM_Result($this->result->table, $this->result->notORM);

        return $result->where($this->result->primary, $this[$this->result->primary])->delete();
    }

    protected function access($key, $delete = false)
    {
        if ($this->result->notORM->cache && !isset($this->modified[$key]) && $this->result->access($key, $delete)) {
            $id = (isset($this->row[$this->result->primary]) ? $this->row[$this->result->primary] : $this->row);
            $this->row = $this->result[$id]->row;
        }
    }

    // IteratorAggregate implementation

    public function getIterator()
    {
        $this->access(null);

        return new ArrayIterator($this->row);
    }

    // Countable implementation

    public function count()
    {
        return count($this->row);
    }

    // ArrayAccess implementation

    /** Test if column exists
    * @param string column name
    * @return bool
    */
    public function offsetExists($key)
    {
        $this->access($key);
        $return = array_key_exists($key, $this->row);
        if (!$return) {
            $this->access($key, true);
        }

        return $return;
    }

    /** Get value of column
    * @param string column name
    * @return string
    */
    public function offsetGet($key)
    {
        $this->access($key);
        if (!array_key_exists($key, $this->row)) {
            $this->access($key, true);
        }

        return $this->row[$key];
    }

    /** Store value in column
    * @param string column name
    * @return null
    */
    public function offsetSet($key, $value)
    {
        $this->row[$key] = $value;
        $this->modified[$key] = $value;
    }

    /** Remove column from data
    * @param string column name
    * @return null
    */
    public function offsetUnset($key)
    {
        unset($this->row[$key]);
        unset($this->modified[$key]);
    }

    // JsonSerializable implementation (not explicit as it is available only since PHP 5.4)

    public function jsonSerialize()
    {
        return $this->row;
    }

}
