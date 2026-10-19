<?php declare(strict_types=1); ?>
--TEST--
Table prefix
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$prefix = new NotORM\NotORM($connection, new NotORM\Structure\Convention(prefix: 'prefix_'));
$applications = $prefix->application("author.name", "Jakub Vrana");
echo "$applications\n";
?>
--EXPECTF--
SELECT application.* FROM prefix_application application LEFT JOIN prefix_author author ON application.author_id = author.id WHERE (author.name = 'Jakub Vrana')
