<?php
namespace NotORM;

/** Representation of filtered table grouped by some column */
class MultiResult extends Result {
	private Result $result;
	private string $column;
	private mixed $active;

	/** @access protected must be public because it is called from Row */
	function __construct(string $table, Result $result, string $column, mixed $active) {
		parent::__construct($table, $result->notORM);
		$this->result = $result;
		$this->column = $column;
		$this->active = $active;
	}

	/** Specify referencing column
	* @return $this fluent interface
	*/
	function via(string $column): static {
		$this->column = $column;
		$this->conditions[0] = "$this->table.$column AND";
		$this->where[0] = "(" . $this->whereIn("$this->table.$column", ($this->result->rows !== null ? array_keys($this->result->rows) : array($this->active))) . ")"; // rows are null after insert
		return $this;
	}

	function insertMulti(array $rows): int|false {
		$args = array();
		foreach ($rows as $data) {
			if ($data instanceof \Traversable && !$data instanceof Result) {
				$data = iterator_to_array($data);
			}
			if (is_array($data)) {
				$data[$this->column] = $this->active;
			}
			$args[] = $data;
		}
		return parent::insertMulti($args);
	}

	function insertUpdate(array $unique, array $insert, array $update = array()): int|false {
		$unique[$this->column] = $this->active;
		return parent::insertUpdate($unique, $insert, $update);
	}

	protected function single(): void {
		$this->where[0] = "($this->column = " . $this->quote($this->active) . ")";
	}

	function update(array $data): int|false {
		$where = $this->where;
		$this->single();
		$return = parent::update($data);
		$this->where = $where;
		return $return;
	}

	function delete(): int|false {
		$where = $this->where;
		$this->single();
		$return = parent::delete();
		$this->where = $where;
		return $return;
	}

	function select(string|Literal $columns, string|Literal ...$moreColumns): static {
		if (!$this->select) {
			return parent::select("$this->table.$this->column", $columns, ...$moreColumns);
		}
		return parent::select($columns, ...$moreColumns);
	}

	function order(string|Literal|array $columns, string|Literal ...$moreColumns): static {
		if (!$this->order) { // improve index utilization
			$this->order[] = "$this->table.$this->column" . (preg_match('~\\bDESC$~i', $columns) ? " DESC" : "");
		}
		return parent::order($columns, ...$moreColumns);
	}

	function group(string|Literal $columns, string|Literal $having = ""): static {
		if ($columns != "") { // group separately for each row of the referenced table
			$columns = "$this->table.$this->column, $columns";
		}
		return parent::group($columns, $having);
	}

	function aggregation(string $function): string|int|float|null {
		$join = $this->createJoins(implode(",", $this->conditions) . ",$function");
		$column = ($join ? "$this->table." : "") . $this->column;
		$query = "SELECT $function, $column FROM $this->table" . implode($join);
		if ($this->where) {
			$query .= " WHERE " . implode($this->where);
		}
		$query .= " GROUP BY $column";
		$aggregation = &$this->result->aggregation[$query];
		if (!isset($aggregation)) {
			$aggregation = array();
			$result = $this->query($query, $this->parameters);
			if ($result) {
				$result->setFetchMode(\PDO::FETCH_ASSOC);
				foreach ($result as $row) {
					$aggregation[$row[$this->column]] = $row;
				}
			}
		}
		if (isset($aggregation[$this->active])) {
			foreach ($aggregation[$this->active] as $return) {
				return $return;
			}
		}
		return null;
	}

	protected function execute(): void {
		if (!isset($this->rows)) {
			$referencing = &$this->result->referencing[$this->__toString()];
			if (!isset($referencing)) {
				if (!$this->limit || count((array) $this->result->rows) <= 1 || $this->union) { // rows are null after insert
					parent::execute();
				} else { //! doesn't work with union
					$result = clone $this;
					$first = true;
					foreach ((array) $this->result->rows as $val) {
						if ($first) {
							$result->where[0] = "$this->column = " . $this->quote($val);
							$first = false;
						} else {
							$clone = clone $this;
							$clone->where[0] = "$this->column = " . $this->quote($val);
							$result->union($clone);
						}
					}
					$result->execute();
					$this->rows = $result->rows;
				}
				$referencing = array();
				foreach ($this->rows as $key => $row) {
					$referencing[$row[$this->column]][$key] = $row;
				}
			}
			$this->data = &$referencing[$this->active];
			if (!isset($this->data)) {
				$this->data = array();
			}
		}
	}

}
