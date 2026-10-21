<?php declare(strict_types=1); ?>
--TEST--
Single row detail
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->application[31];
foreach ($application as $key => $val) {
	echo "$key: $val\n";
}
?>
--EXPECTF--
id: 31
author_id: 11
maintainer_id: 11
title: Adminer
web: https://www.adminer.org/
slogan: Database management in a single PHP file
logo: 
