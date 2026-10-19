<?php declare(strict_types=1); ?>
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
34
33
32
31
23
22
21
