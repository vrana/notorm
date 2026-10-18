<?php
namespace NotORM\Structure;

/** Structure described by some rules
*/
class Convention implements \NotORM\Structure {
	protected $primary;
	protected $foreign;
	protected $table;
	protected $prefix;
	protected $sequence;

	/** Create conventional structure
	* @param string $primary %s stands for table name
	* @param string $foreign %1$s stands for key used after ->, %2$s for table name
	* @param string $table %1$s stands for key used after ->, %2$s for table name
	* @param string $prefix prefix for all tables
	* @param string|null|false $sequence %s stands for table name, e.g. "%s_id_seq" in PostgreSQL; null if the name is not needed, false to not get the inserted ID
	*/
	function __construct($primary = 'id', $foreign = '%s_id', $table = '%s', $prefix = '', $sequence = null) {
		$this->primary = $primary;
		$this->foreign = $foreign;
		$this->table = $table;
		$this->prefix = $prefix;
		$this->sequence = $sequence;
	}

	function getPrimary($table) {
		return sprintf($this->primary, $this->getColumnFromTable($table));
	}

	function getReferencingColumn($name, $table) {
		return $this->getReferencedColumn(substr($table, strlen($this->prefix)), $this->prefix . $name);
	}

	function getReferencingTable($name, $table) {
		return $this->prefix . $name;
	}

	function getReferencedColumn($name, $table) {
		return sprintf($this->foreign, $this->getColumnFromTable($name), substr($table, strlen($this->prefix)));
	}

	function getReferencedTable($name, $table) {
		return $this->prefix . sprintf($this->table, $name, $table);
	}

	function getSequence($table) {
		return (is_string($this->sequence) ? sprintf($this->sequence, $table) : $this->sequence);
	}

	protected function getColumnFromTable($name) {
		if ($this->table != '%s' && preg_match('(^' . str_replace('%s', '(.*)', preg_quote($this->table)) . '$)', $name, $match)) {
			return $match[1];
		}
		return $name;
	}

}
