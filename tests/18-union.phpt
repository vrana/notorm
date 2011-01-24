--TEST--
Complex UNION
--SKIPIF--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
echo (preg_match('~^(sqlite|oci)$~', $driver) ? "Not supported in $driver.\n" : "");
?>
--FILE--
<?php
$applications = $software->application()->select("id")->order("id DESC")->limit(2);
$tags = $software->tag()->select("id")->order("id")->limit(2);
foreach ($applications->union($tags)->order("id DESC") as $row) {
	echo "$row[id]\n";
}
?>
--EXPECTF--
22
21
4
3
