<?php declare(strict_types=1); ?>
--TEST--
Duplicate or missing primary keys (bug #80)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$authors = $software->author()->select("author.*, application:title")->order("application:title");
foreach ($authors as $key => $author) {
	echo "$key: $author[name] - $author[title], " . count($author->application()) . " applications\n";
}
echo "\n";

foreach ($software->application("id", [1, 2])->select("title, CASE WHEN id = 1 THEN 1 END AS id")->order("title") as $key => $application) {
	echo "$key: $application[title]\n";
}
echo "\n";

foreach ($software->author()->order("id") as $author) {
	foreach ($author->application()->select("application.*, application_tag:tag_id")->order("application.title, application_tag:tag_id") as $key => $application) {
		echo "$key: $author[name] - $application[title] $application[tag_id]\n";
	}
	echo "\n";
}

$_SESSION = [];
$cache = new NotORM\NotORM($connection, cache: new NotORM\Cache\Session);
foreach (["name", "web"] as $column) {
	$authors = $cache->author("application:title LIKE ?", "%")->order("author.id");
	foreach ($authors as $key => $author) {
		echo "$key: $author[$column]\n"; // the second time re-executes the result with all columns
	}
	$authors->__destruct();
	echo "\n";
}
?>
--EXPECTF--
0: Jakub Vrana - Adminer, 2 applications
1: David Grudl - Dibi, 2 applications
2: Jakub Vrana - JUSH, 2 applications
3: David Grudl - Nette, 2 applications

0: Adminer
1: JUSH

0: Jakub Vrana - Adminer 21
1: Jakub Vrana - Adminer 22
2: Jakub Vrana - JUSH 23

0: David Grudl - Dibi 21
1: David Grudl - Dibi 22
2: David Grudl - Nette 21

0: Jakub Vrana
1: Jakub Vrana
2: David Grudl
3: David Grudl

0: http://www.vrana.cz/
1: http://www.vrana.cz/
2: http://davidgrudl.com/
3: http://davidgrudl.com/

