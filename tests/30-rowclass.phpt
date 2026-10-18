--TEST--
Custom row class
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

class TestRow extends NotORM\Row {
	
	function offsetExists($key) {
		return parent::offsetExists(preg_replace('~^test_~', '', $key));
	}
	
	function offsetGet($key) {
		return parent::offsetGet(preg_replace('~^test_~', '', $key));
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
