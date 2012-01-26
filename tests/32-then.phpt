--TEST--
Passing result to callback
--SKIPIF--
<?php
echo (version_compare(PHP_VERSION, '5.3.0') < 0 ? "PHP 5.3+ required\n" : "");
?>
--FILE--
<?php
if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
	// in a separate file to avoid syntax errors
	include dirname(__FILE__) . "/32-then.php";
}
?>
--EXPECTF--
Authors:
Jakub Vrana
David Grudl

Application tags:
Adminer: PHP
Adminer: MySQL
JUSH: JavaScript
Nette: PHP
Dibi: PHP
Dibi: MySQL
