--TEST--
Table prefix
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$prefix = new NotORM($connection, new NotORM_Structure_Convention('id', '%s_id', '%s', 'prefix_'));
$applications = $prefix->application("author.name", "Jakub Vrana");
echo "$applications\n";
?>
--EXPECTF--
SELECT prefix_application.* FROM prefix_application LEFT JOIN prefix_author AS author ON prefix_application.author_id = author.id WHERE (author.name = 'Jakub Vrana')
