--TEST--
fetchPairs()
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

print_r($software->application()->order("title")->fetchPairs("id", "title"));
?>
--EXPECTF--
Array
(
    [1] => Adminer
    [4] => Dibi
    [2] => JUSH
    [3] => Nette
)
