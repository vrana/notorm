--TEST--
Session cache
--FILE--
<?php
$_SESSION = array(); // not session_start() - headers already sent
include_once dirname(__FILE__) . "/connect.inc.php";
$cache = new NotORM($connection, null, new NotORM_Cache_Session);

$applications = $cache->application();
echo "$applications\n"; // get all
foreach ($applications as $id => $application) {
	$application["title"];
	$application->author["name"];
}
$applications->__destruct();

$applications = $cache->application();
echo "$applications\n"; // next time, get only title and author_id
foreach ($applications as $application) {
	$application["slogan"]; // script changed and now we want also slogan
}
echo "$applications\n"; // next time, get only title and author_id
$applications->__destruct();

echo $cache->application() . "\n";
?>
--EXPECTF--
SELECT * FROM application
SELECT id, title, author_id FROM application
SELECT * FROM application
SELECT id, title, author_id, slogan FROM application
