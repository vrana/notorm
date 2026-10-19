<?php declare(strict_types=1); ?>
--TEST--
fetchPairs() of an already fetched result
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$software->debug = function (string $query): void {
	echo "$query\n";
};

$applications = $software->application()->order("id");
iterator_to_array($applications);
echo implode(", ", array_keys($applications->fetchPairs("title"))) . "\n";

$applications = $software->application()->select("id, title")->order("id");
iterator_to_array($applications);
echo implode(", ", array_keys($applications->fetchPairs("title"))) . "\n";

$software->debug = false;
?>
--EXPECTF--
SELECT * FROM application ORDER BY id
SELECT title, application.* FROM application ORDER BY id
Adminer, JUSH, Nette, Dibi
SELECT id, title FROM application ORDER BY id
SELECT title, id, title FROM application ORDER BY id
Adminer, JUSH, Nette, Dibi
