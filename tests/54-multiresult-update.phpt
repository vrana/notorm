<?php declare(strict_types=1); ?>
--TEST--
Update the referencing rows of one row
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$slogans = $software->application()->fetchPairs("id", "slogan");
foreach ($software->author()->order("id") as $author) {
	if ($author["id"] == 11) {
		echo $author->application()->update(["slogan" => "Updated"]) . " rows updated\n";
	}
}
foreach ($software->application()->order("id") as $application) {
	echo "$application[id]: $application[slogan]\n";
}
foreach ($slogans as $id => $slogan) {
	$software->application("id", $id)->update(["slogan" => $slogan]);
}
?>
--EXPECTF--
2 rows updated
31: Updated
32: Updated
33: Nette Framework for PHP 5
34: Database Abstraction Library for PHP 5
