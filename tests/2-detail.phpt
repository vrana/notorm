--TEST--
Single row detail
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$application = $software->application[1];
echo "$application[title]\n";
?>
--EXPECTF--
Adminer
