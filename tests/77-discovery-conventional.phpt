<?php declare(strict_types=1); ?>
--TEST--
Discovery prefers the conventional one of more referencing columns
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";
$discovery = new NotORM\NotORM($connection, new NotORM\Structure\Discovery($connection, foreign: "%s_id"));

// application.author_id and application.maintainer_id both reference author
$author = $discovery->author[11];
echo $author->application() . "\n";
echo $author->application()->count("*") . "\n";
echo $author->application()->via("maintainer_id")->count("*") . "\n";
?>
--EXPECTF--
SELECT * FROM application WHERE (application.author_id IN (11))
2
1
