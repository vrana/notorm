--TEST--
Search and order items
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($blog->article("published <= NOW()")->order("published DESC") as $article) {
	echo "$article[title]\n";
}
?>
--EXPECTF--
Já a PHP
