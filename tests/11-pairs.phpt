<?php declare(strict_types=1); ?>
--TEST--
fetchPairs()
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

print_r($software->application()->order("title")->fetchPairs("id", "title"));
print_r($software->application()->order("id")->fetchPairs("id", "id"));
?>
--EXPECTF--
Array
(
    [31] => Adminer
    [34] => Dibi
    [32] => JUSH
    [33] => Nette
)
Array
(
    [31] => 31
    [32] => 32
    [33] => 33
    [34] => 34
)
