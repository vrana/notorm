<?php
/**
 * Created by PhpStorm.
 * User: sim
 * Date: 27.02.16
 * Time: 17:02
 */
namespace NotORM;

/** Structure described by some rules
 */
class StructureConvention implements StructureInterface
{
	protected $primary, $foreign, $table, $prefix;

	/** Create conventional structure
	 * @param string $primary %s stands for table name
	 * @param string $foreign %1$s stands for key used after ->, %2$s for table name
	 * @param string $table %1$s stands for key used after ->, %2$s for table name
	 * @param string $prefix $prefix for all tables
	 */
	public function __construct($primary = 'id', $foreign = '%s_id', $table = '%s', $prefix = '')
	{
		$this->primary = $primary;
		$this->foreign = $foreign;
		$this->table = $table;
		$this->prefix = $prefix;
	}

	/**
	 * Get primary
	 * @param string $table
	 * @return string
	 */
	public function getPrimary($table)
	{
		return sprintf($this->primary, $this->getColumnFromTable($table));
	}

	/**
	 * Get column name
	 * @param string $name
	 * @return string
	 */
	protected function getColumnFromTable($name)
	{
		if ($this->table != '%s' && preg_match('(^' . str_replace('%s', '(.*)', preg_quote($this->table)) . '$)', $name, $match)) {
			return $match[1];
		}
		return $name;
	}

	/**
	 * Get referencing column
	 * @param string $name
	 * @param string $table
	 * @return string
	 */
	public function getReferencingColumn($name, $table)
	{
		return $this->getReferencedColumn(substr($table, strlen($this->prefix)), $this->prefix . $name);
	}

	/**
	 * Get referenced column
	 * @param string $name
	 * @param string $table
	 * @return string
	 */
	public function getReferencedColumn($name, $table)
	{
		return sprintf($this->foreign, $this->getColumnFromTable($name), substr($table, strlen($this->prefix)));
	}

	/**
	 * Get referencing table
	 * @param string $name
	 * @param string $table
	 * @return string
	 */
	public function getReferencingTable($name, $table)
	{
		return $this->prefix . $name;
	}

	/**
	 * Get referenced table
	 * @param string $name
	 * @param string $table
	 * @return string
	 */
	public function getReferencedTable($name, $table)
	{
		return $this->prefix . sprintf($this->table, $name, $table);
	}

	/**
	 * Get sequence
	 * @param string $table
	 * @return null
	 */
	public function getSequence($table)
	{
		//TODO Why getSequence(?) always return null
		return null;
	}

}