<?php declare(strict_types=1); ?>
--TEST--
UNION with limit and offset
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$ids = fn ($result) => implode(", ", array_map(fn ($row) => $row["id"], iterator_to_array($result, false)));
$union = $software->application()->select("id")->union($software->tag()->select("id"))->order("id");
echo $ids($union->limit(2)) . "\n";
echo $ids($union->limit(2, 3)) . "\n";
?>
--EXPECTF--
21, 22
31, 32
