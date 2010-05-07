--TEST--
Aggregation functions
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

list($count) = $software->application()->group("COUNT(*)");
echo "$count applications\n";
foreach ($software->application() as $application) {
	list($count) = $application->application_tag()->group("COUNT(*)");
	echo "$application[title]: $count tag(s)\n";
}
?>
--EXPECTF--
4 applications
Adminer: 2 tag(s)
JUSH: 1 tag(s)
Nette: 1 tag(s)
Dibi: 2 tag(s)
