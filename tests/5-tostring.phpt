--TEST--
Calling __toString()
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($blog->article()->limit(10) as $article) {
	echo "$article\n";
}
?>
--EXPECTF--
1
2
3
4
5
6
7
8
9
10
