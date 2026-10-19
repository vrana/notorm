<?php declare(strict_types=1); ?>
--TEST--
Parameters in UNION parts (bug #41)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$applications = $software->application()->select(new NotORM\Literal("id + ? AS id", 0))->where("id < ?", 33);
$tags = $software->tag()->select(new NotORM\Literal("id + ? AS id", 0))->where("id > ?", 22);
foreach ($applications->union($tags)->order("id DESC") as $row) {
	echo "$row[id]\n";
}
?>
--EXPECTF--
32
31
23
