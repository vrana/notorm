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
echo $software->application[2]->application_tag("tag_id", 21)->count("*") . "\n";
?>
--EXPECTF--
2
1
1, 2, 3, 4
SELECT * FROM application
SELECT * FROM application
bool(false)
0
0
0
