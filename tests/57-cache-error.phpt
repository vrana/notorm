<?php declare(strict_types=1); ?>
--TEST--
Cached column which doesn't exist anymore
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$_SESSION = [];
$cache = new NotORM\NotORM($connection, cache: new NotORM\Cache\Session);
$_SESSION["NotORM"][NotORM\Cache::VERSION . ";application;"] = ["id" => true, "renamed" => true];
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$applications = $cache->application()->order("id");
echo $applications->fetch()["title"] . "\n";
echo "$applications\n"; // all columns after the error
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
?>
--EXPECTF--
Adminer
SELECT * FROM application ORDER BY id
