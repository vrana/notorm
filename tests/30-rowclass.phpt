--TEST--
Custom row class
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

class TestRow extends NotORM\Row {

	function offsetExists($offset): bool {
		return parent::offsetExists(preg_replace('~^test_~', '', $offset));
	}

	function offsetGet($offset): mixed {
		return parent::offsetGet(preg_replace('~^test_~', '', $offset));
	}

}

$software->rowClass = 'TestRow';

$application = $software->application[1];
echo "$application[test_title]\n";
echo $application->author["test_name"] . "\n";

$software->rowClass = 'NotORM\Row';
?>
--EXPECTF--
Adminer
Jakub Vrana
