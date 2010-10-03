--TEST--
Aggregation functions
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$count = $software->application()->count("*");
echo "$count applications\n";
foreach ($software->application() as $application) {
	$count = $application->application_tag()->count("*");
	echo "$application[title]: $count tag(s)\n";
}
?>
--EXPECTF--
4 applications
Adminer: 2 tag(s)
JUSH: 1 tag(s)
Nette: 1 tag(s)
Dibi: 2 tag(s)
