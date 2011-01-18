--TEST--
Array offset
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

echo $software->application[array("title" => "Adminer")]["id"] . "\n";
?>
--EXPECTF--
1
