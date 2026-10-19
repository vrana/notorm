<?php declare(strict_types=1); ?>
--TEST--
Backwards join
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

foreach ($software->author()->select("author.id, author.name, COUNT(DISTINCT application:application_tag:tag_id) AS tags")->group("author.id, author.name")->order("tags DESC") as $autor) {
	echo "$autor[name]: $autor[tags]\n";
}
?>
--EXPECTF--
Jakub Vrana: 3
David Grudl: 2
