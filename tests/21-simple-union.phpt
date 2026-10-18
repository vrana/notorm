--TEST--
Simple UNION
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$applications = $software->application()->select("id");
$tags = $software->tag()->select("id");
foreach ($applications->union($tags)->order("id DESC") as $row) {
	echo "$row[id]\n";
}
?>
--EXPECTF--
23
22
21
4
3
2
1
