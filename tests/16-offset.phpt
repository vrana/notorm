--TEST--
Limit and offset
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$application = $software->application[1];
foreach ($application->application_tag()->order("tag_id")->limit(1, 1) as $application_tag) {
	echo $application_tag->tag["name"] . "\n";
}
echo "\n";

foreach ($software->application() as $application) {
	foreach ($application->application_tag()->order("tag_id")->limit(1, 1) as $application_tag) {
		echo $application_tag->tag["name"] . "\n";
	}
}
?>
--EXPECTF--
MySQL

MySQL
MySQL
