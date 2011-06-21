--TEST--
fetchPairs()
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

print_r($software->application()->order("title")->fetchPairs("id", "title"));
print_r($software->application()->order("id")->fetchPairs("id", "id"));
?>
--EXPECTF--
Array
(
    [1] => Adminer
    [4] => Dibi
    [2] => JUSH
    [3] => Nette
)
Array
(
    [1] => 1
    [2] => 2
    [3] => 3
    [4] => 4
)
