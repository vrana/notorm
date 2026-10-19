--TEST--
Cache in a database table
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // MySQL doesn't count unchanged rows in UPDATE so save() tries INSERT
foreach (["empty", "filled"] as $state) {
	$cache = new NotORM\NotORM($connection, cache: new NotORM\Cache\Database($connection));
	$applications = $cache->application();
	$applications->fetch()["title"];
	echo "$applications\n";
	$applications->__destruct();
}
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
?>
--EXPECTF--
SELECT * FROM application
SELECT id, title FROM application
