<?php
namespace NotORM\Structure;

/** Structure described by some rules */
class Convention implements \NotORM\Structure {
	protected string $primary;
	protected string $foreign;
	protected string $table;
	protected string $prefix;
	protected string|null|false $sequence;

	/** Create conventional structure
	* @param string $primary %s stands for table name
	* @param string $foreign %1$s stands for key used after ->, %2$s for table name
	* @param string $table %1$s stands for key used after ->, %2$s for table name
	* @param string $prefix prefix for all tables
	* @param string|null|false $sequence %s stands for table name, e.g. "%s_id_seq" in PostgreSQL; null if the name is not needed, false to not get the inserted ID
	*/
	function __construct(string $primary = 'id', string $foreign = '%s_id', string $table = '%s', string $prefix = '', string|null|false $sequence = null) {
		$this->primary = $primary;
		$this->foreign = $foreign;
		$this->table = $table;
		$this->prefix = $prefix;
		$this->sequence = $sequence;
	}

	function getPrimary(string $table): string {
		return sprintf($this->primary, $this->getColumnFromTable($table));
	}

	function getReferencingColumn(string $name, string $table): string {
		return $this->getReferencedColumn(substr($table, strlen($this->prefix)), $this->prefix . $name);
	}

	function getReferencingTable(string $name, string $table): string {
		return $this->prefix . $name;
	}

	function getReferencedColumn(string $name, string $table): string {
		return sprintf($this->foreign, $this->getColumnFromTable($name), substr($table, strlen($this->prefix)));
	}

	function getReferencedTable(string $name, string $table): string {
		return $this->prefix . sprintf($this->table, $name, $table);
	}

	function getSequence(string $table): string|false|null {
		return (is_string($this->sequence) ? sprintf($this->sequence, $table) : $this->sequence);
	}

	protected function getColumnFromTable(string $name): string {
		if ($this->table != '%s' && preg_match('(^' . str_replace('%s', '(.*)', preg_quote($this->table)) . '$)', $name, $match)) {
			return $match[1];
		}
		return $name;
	}

}
