<?php declare(strict_types=1); ?>
--TEST--
Aggregation with UNION, GROUP BY and LIMIT (bug #79)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

echo $software->application()->select("title AS name")->union($software->tag()->select("name"))->max("name") . "\n";
echo $software->application()->select("title AS name")->union($software->tag("id > ?", 21)->select("name"))->count("*") . "\n";
echo $software->application()->group("author_id")->count("*") . "\n";
echo $software->application()->group("maintainer_id", "COUNT(*) > 1")->count("*") . "\n";
echo $software->application()->order("id")->limit(2)->sum("id") . "\n";
echo $software->application()->order("id")->limit(2, 1)->sum("id") . "\n";
echo "\n";

foreach ($software->author()->order("id") as $author) {
	echo "$author[name]: " . $author->application()->group("maintainer_id")->count("*");
	echo ", " . $author->application()->order("id")->limit(1)->sum("id");
	echo ", " . $author->application()->order("id")->limit(1, 1)->sum("id") . "\n";
}
?>
--EXPECTF--
PHP
6
2
1
3
5

Jakub Vrana: 2, 1, 2
David Grudl: 1, 3, 4
