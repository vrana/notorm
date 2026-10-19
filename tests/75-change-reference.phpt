<?php declare(strict_types=1); ?>
--TEST--
Read a reference after changing it
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$software->debug = function (string $query): void {
	echo "$query\n";
};

$application = $software->application[31];
echo $application->author["name"] . "\n";
$application->author = $software->author[12];
echo "$application[author_id]: " . $application->author["name"] . "\n";
$application["author_id"] = 11;
echo $application->author["name"] . "\n";

$software->debug = false;
?>
--EXPECTF--
SELECT * FROM application WHERE (id = 31)
SELECT * FROM author WHERE (author.id IN (11))
Jakub Vrana
SELECT * FROM author WHERE (id = 12)
SELECT * FROM author WHERE (author.id IN (12))
12: David Grudl
SELECT * FROM author WHERE (author.id IN (11))
Jakub Vrana
