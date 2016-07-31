<?php
namespace NotORM;

 /**
 * @property mixed $debug = false Enable debugging queries, true for error_log($query), callback($query, $parameters) otherwise
 * @property bool $freeze = false Disable persistence
 * @property string $rowClass = 'NotORM/Row' Class used for created objects
 * @property bool $jsonAsArray = false Use array instead of object in Result JSON serialization
 * @property string $transaction Assign 'BEGIN', 'COMMIT' or 'ROLLBACK' to start or stop transaction
 */

abstract class AbstractClass {
	protected $connection, $driver, $structure, $cache;
	protected $notORM, $table, $primary, $rows, $referenced = array();

	protected $debug = false;
	protected $debugTimer;
	protected $freeze = false;
	protected $rowClass = 'NotORM\Row';
	protected $jsonAsArray = false;

	protected abstract function access($key, $delete = false);

}