<?php declare(strict_types=1); ?>
--TEST--
Default fetch mode (bug #108)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

foreach ($software->author() as $author) {
	echo "$author[name]: " . $author->application()->count("*") . "\n";
}

$discovery = new NotORM\NotORM($connection, new NotORM\Structure\Discovery($connection));
echo $discovery->application[1]->author_id["name"] . "\n";

$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_BOTH);
?>
--EXPECTF--
Jakub Vrana: 2
David Grudl: 2
Jakub Vrana
