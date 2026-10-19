<?php declare(strict_types=1); ?>
--TEST--
File caches starting without the file
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

foreach ([NotORM\Cache\File::class, NotORM\Cache\IncludeFile::class] as $class) {
	$filename = sys_get_temp_dir() . "/notorm-" . getmypid() . ".php";
	@unlink($filename); // @ - file may not exist
	foreach (["not exists", "exists"] as $state) {
		$cache = new NotORM\NotORM($connection, cache: new $class($filename));
		$applications = $cache->application();
		$application = $applications->fetch();
		$application["title"];
		echo "$applications\n";
		$applications->__destruct();
	}
	unlink($filename);
}
?>
--EXPECTF--
SELECT * FROM application
SELECT id, title FROM application
SELECT * FROM application
SELECT id, title FROM application
