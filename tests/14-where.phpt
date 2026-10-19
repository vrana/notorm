<?php declare(strict_types=1); ?>
--TEST--
WHERE
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$results = [
	$software->application("id", 34),
	$software->application("id < ?", 34),
	$software->application("id < ?", [34]),
	$software->application("id", [31, 32]),
	$software->application("id", null),
	$software->application("id", $software->application()),
	$software->application("id < ?", 34)->where("maintainer_id IS NOT NULL"),
	$software->application("NOT maintainer_id", null),
	$software->application(["id < ?" => 34, "author_id" => 12]),
];
foreach ($results as $result) {
	echo implode(", ", array_keys(iterator_to_array($result->order("id")))) . "\n"; // aggregation("GROUP_CONCAT(id)") is not available in all drivers
}
?>
--EXPECTF--
34
31, 32, 33
31, 32, 33
31, 32

31, 32, 33, 34
31, 33
31, 33, 34
33
