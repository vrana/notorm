--TEST--
Find one item by URL
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$article = $blog->article("url", array("Já a PHP"))->fetch();
foreach ($article->article_tag() as $article_tag) {
	echo $article_tag->tag["name"] . "\n";
}
?>
--EXPECTF--
Osobní
