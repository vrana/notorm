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
            [1] => Adminer
            [2] => JUSH
        )

    [12] => Array
        (
            [3] => Nette
            [4] => Dibi
        )

)
Jakub Vrana: Adminer, JUSH
David Grudl: Nette, Dibi
Array
(
    [Adminer] => Array
        (
            [1] => 1
        )

    [JUSH] => Array
        (
            [2] => 2
        )

)
