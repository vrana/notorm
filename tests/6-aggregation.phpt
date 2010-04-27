--TEST--
Aggregation functions
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->application() as $application) {
	echo "$application[title]: " . count($application->application_tag()) . "\n";
}
?>
--EXPECTF--
Adminer: 2
JUSH: 1
Nette: 1
dibi: 2
