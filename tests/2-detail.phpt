--TEST--
Single row detail
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$article = $blog->article[1];
echo "$article[title]\n";
?>
--EXPECTF--
Já a PHP
