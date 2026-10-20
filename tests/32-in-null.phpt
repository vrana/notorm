<?php declare(strict_types=1); ?>
--TEST--
IN with NULL value
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$print = function (NotORM\Result $applications): void {
	echo "$applications\n";
	echo implode(", ", array_keys(iterator_to_array($applications))) . "\n";
	echo "\n";
};

$print($software->application("maintainer_id", [11, null])->order("id"));
$print($software->application("maintainer_id", [null])->order("id"));
$print($software->application("maintainer_id", [])->order("id"));
$print($software->application("NOT maintainer_id", [11, null])->order("id"));
$print($software->application("NOT maintainer_id", [null])->order("id"));
$print($software->application("NOT maintainer_id", [])->order("id"));
?>
--EXPECTF--
SELECT * FROM application WHERE (maintainer_id IN (11) OR maintainer_id IS NULL) ORDER BY id
31, 32

SELECT * FROM application WHERE (maintainer_id IS NULL) ORDER BY id
32

SELECT * FROM application WHERE ((maintainer_id) IS NOT NULL AND maintainer_id IS NULL) ORDER BY id


SELECT * FROM application WHERE (NOT (maintainer_id IN (11) OR maintainer_id IS NULL)) ORDER BY id
33, 34

SELECT * FROM application WHERE (maintainer_id IS NOT NULL) ORDER BY id
31, 33, 34

SELECT * FROM application WHERE (maintainer_id IS NOT NULL) ORDER BY id
31, 33, 34

