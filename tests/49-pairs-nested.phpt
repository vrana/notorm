<?php declare(strict_types=1); ?>
--TEST--
fetchPairs() with more keys (bug #103)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

print_r($software->application()->order("id")->fetchPairs(["author_id", "id"], "title"));
foreach ($software->application()->order("application.id")->fetchPairs(["author.name", "application.id"]) as $name => $applications) {
	echo "$name: " . implode(", ", array_map(fn ($application) => $application["title"], $applications)) . "\n";
}
print_r($software->author[11]->application()->order("id")->fetchPairs(["title", "id"], "id"));
?>
--EXPECTF--
Array
(
    [11] => Array
        (
            [31] => Adminer
            [32] => JUSH
        )

    [12] => Array
        (
            [33] => Nette
            [34] => Dibi
        )

)
Jakub Vrana: Adminer, JUSH
David Grudl: Nette, Dibi
Array
(
    [Adminer] => Array
        (
            [31] => 31
        )

    [JUSH] => Array
        (
            [32] => 32
        )

)
