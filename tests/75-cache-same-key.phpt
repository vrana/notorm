<?php declare(strict_types=1); ?>
--TEST--
Cache of results with the same conditions using different columns
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$_SESSION = [];
$cache = new NotORM\NotORM($connection, cache: new NotORM\Cache\Session);
$cache->debug = function (string $query): void {
	echo "$query\n";
};
foreach (["empty", "filled"] as $state) {
	$applications1 = $cache->application("id", 31);
	$applications1->fetch()["web"];
	$applications2 = $cache->application("id", 32);
	$applications2->fetch()["slogan"];
	$applications1->__destruct();
	$applications2->__destruct();
	echo "\n";
}
?>
--EXPECTF--
SELECT * FROM application WHERE (id = 31)
SELECT * FROM application WHERE (id = 32)

SELECT id, web, slogan FROM application WHERE (id = 31)
SELECT id, web, slogan FROM application WHERE (id = 32)

