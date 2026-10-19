--TEST--
Cache without a nonexistent column and with all columns after iterating a row
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$_SESSION = [];
$cache = new NotORM\NotORM($connection, cache: new NotORM\Cache\Session);

$applications = $cache->application();
$application = $applications->fetch();
$application["title"];
isset($application["nonexistent"]);
echo "$applications\n"; // all columns with no cache
$applications->__destruct();

$applications = $cache->application();
$application = $applications->fetch();
echo "$applications\n"; // only title, not the nonexistent column
iterator_to_array($application); // all columns
$applications->__destruct();

$applications = $cache->application();
$applications->fetch();
echo "$applications\n";
?>
--EXPECTF--
SELECT * FROM application
SELECT id, title FROM application
SELECT * FROM application
