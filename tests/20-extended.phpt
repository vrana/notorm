--TEST--
Extended insert
--SKIPIF--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
echo (preg_match('~^(sqlite|oci)$~', $driver) ? "Not supported in $driver.\n" : "");
?>
--FILE--
<?php
$application = $software->application[3];
$application->application_tag()->insert(array("tag_id" => 22), array("tag_id" => 23));
foreach ($application->application_tag()->order("tag_id DESC") as $application_tag) {
	echo "$application_tag[application_id] $application_tag[tag_id]\n";
}
$application->application_tag("tag_id", array(22, 23))->delete();
?>
--EXPECTF--
3 23
3 22
3 21
