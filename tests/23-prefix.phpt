--TEST--
Table prefix
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$prefix = new NotORM\NotORM($connection, new NotORM\Structure\Convention('id', '%s_id', '%s', 'prefix_'));
$applications = $prefix->application("author.name", "Jakub Vrana");
echo "$applications\n";
?>
--EXPECTF--
SELECT prefix_application.* FROM prefix_application LEFT JOIN prefix_author AS author ON prefix_application.author_id = author.id WHERE (author.name = 'Jakub Vrana')
