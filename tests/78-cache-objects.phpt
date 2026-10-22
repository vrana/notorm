<?php declare(strict_types=1); ?>
--TEST--
Caches don't create objects from the stored data
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$filename = sys_get_temp_dir() . "/notorm-" . getmypid() . ".dat";
file_put_contents($filename, serialize(["object" => new ArrayObject(["title" => true])]));
echo get_class((new NotORM\Cache\File($filename))->load("object")) . "\n";
unlink($filename);

$cache = new NotORM\Cache\Database($connection);
$cache->save("object", new ArrayObject(["title" => true]));
echo get_class($cache->load("object")) . "\n";
$software->notorm("id", "object")->delete();
?>
--EXPECTF--
__PHP_Incomplete_Class
__PHP_Incomplete_Class
