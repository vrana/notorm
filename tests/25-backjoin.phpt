--TEST--
Backwards join
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->author()->select("author.*, COUNT(DISTINCT application:application_tag:tag_id) AS tags")->group("author.id")->order("tags DESC") as $autor) {
	echo "$autor[name]: $autor[tags]\n";
}
?>
--EXPECTF--
Jakub Vrana: 3
David Grudl: 2
