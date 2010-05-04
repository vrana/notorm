--TEST--
Session cache
--FILE--
<?php
$_SESSION = array(); // not session_start() - headers already sent
include_once dirname(__FILE__) . "/connect.inc.php";
$cache = new NotORM($connection, null, new NotORM_Cache_Session);

$applications = $cache->application();
$application = $applications->fetch();
$application["title"];
$application->author["name"];
echo "$applications\n"; // get all columns with no cache
$applications->__destruct();

$applications = $cache->application();
$application = $applications->fetch();
echo "$applications\n"; // get only title and author_id
$application["slogan"]; // script changed and now we want also slogan
echo "$applications\n"; // all columns must have been retrieved to get slogan
$applications->__destruct();

$applications = $cache->application();
$applications->fetch();
echo "$applications\n"; // next time, get only title, author_id and slogan
?>
--EXPECTF--
SELECT * FROM application
SELECT id, title, author_id FROM application
SELECT * FROM application
SELECT id, title, author_id, slogan FROM application
