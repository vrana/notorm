<?php declare(strict_types=1); ?>
--TEST--
Complex UNION
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$applications = $software->application()->select("id")->order("id DESC")->limit(2);
$tags = $software->tag()->select("id")->order("id")->limit(2);
foreach ($applications->union($tags)->order("id DESC") as $row) {
	echo "$row[id]\n";
}
?>
--EXPECTF--
34
33
22
21
