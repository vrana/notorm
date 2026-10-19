<?php declare(strict_types=1); ?>
--TEST--
Relation with a column not in the cache (bug #63)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$_SESSION = [];
$cache = new NotORM\NotORM($connection, cache: new NotORM\Cache\Session);
$applications = $cache->application()->order("id");
foreach ($applications as $application) {
	$application["title"];
}
$applications->__destruct();

$applications = $cache->application()->order("id");
foreach ($applications as $application) {
	echo "$application[title]: " . $application->author["name"] . "\n";
}
?>
--EXPECTF--
Adminer: Jakub Vrana
JUSH: Jakub Vrana
Nette: David Grudl
Dibi: David Grudl
