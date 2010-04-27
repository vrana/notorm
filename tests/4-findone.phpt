--TEST--
Find one item by URL
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$application = $software->application("title", "Adminer")->fetch();
foreach ($application->application_tag() as $application_tag) {
	echo $application_tag->tag["name"] . "\n";
}
?>
--EXPECTF--
PHP
MySQL
