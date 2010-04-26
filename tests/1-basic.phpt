--TEST--
Basic operations
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($blog->article()->limit(10) as $article) {
	echo $article->author["name"] . ": $article[title]\n";
}
?>
--EXPECTF--
Jakub Vrána: Já a PHP
Jakub Vrána: Procházení polí
Jakub Vrána: Výpis polí
Jakub Vrána: Inicializace proměnných
Jakub Vrána: Proměnné zvenku
Jakub Vrána: Obrana proti SQL Injection
Jakub Vrána: PHP Internals
Jakub Vrána: Konfigurace PHP
Jakub Vrána: Připojení k databázi
Jakub Vrána: Výběr kódování znaků
