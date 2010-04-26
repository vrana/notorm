--TEST--
Aggregation functions
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($blog->article()->limit(10) as $article) {
	echo $article->article_tag()->count() . "\n";
}
?>
--EXPECTF--
1
1
1
1
1
1
1
1
1
1
