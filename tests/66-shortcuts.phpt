<?php declare(strict_types=1); ?>
--TEST--
Shortcuts and resetting clauses
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

echo count($software->application()("author_id", 11)) . "\n";
echo $software->application()->min("id") . "\n";
echo implode(", ", array_keys($software->application()->select("title")->order("id")->fetchPairs("id"))) . "\n";
echo $software->application()->select("title")->select("") . "\n";
echo $software->application()->order("title")->order("") . "\n";
var_dump(str_contains((string) $software->application()->select("id")->union($software->tag()->select("id"))->order("id")->order(""), "ORDER BY"));
echo $software->tag("id", 21)->update([]) . "\n";
echo $software->tag()->insertMulti([]) . "\n";
echo $software->application[32]->application_tag("tag_id", 21)->count("*") . "\n";
?>
--EXPECTF--
2
31
31, 32, 33, 34
SELECT * FROM application
SELECT * FROM application
bool(false)
0
0
0
