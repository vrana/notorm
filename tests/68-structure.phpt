--TEST--
Convention with a table format and Discovery with a cache
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$plural = new NotORM\NotORM($connection, new NotORM\Structure\Convention(primary: '%s_id', table: '%ss'));
echo $plural->applications("author.name", "Jakub Vrana") . "\n";

$_SESSION = [];
$discovery = new NotORM\Structure\Discovery($connection, new NotORM\Cache\Session);
var_dump($discovery->getPrimary("setting"));
unset($discovery);
echo implode(", ", array_keys($_SESSION["NotORM"])) . "\n";
?>
--EXPECTF--
SELECT applications.* FROM applications LEFT JOIN authors AS author ON applications.author_id = author.author_id WHERE (author.name = 'Jakub Vrana')
string(0) ""
2;structure
