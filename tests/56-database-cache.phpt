--TEST--
Cache in a database table
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

@$connection->exec("DROP TABLE notorm"); // @ - left by an interrupted run
$connection->exec("CREATE TABLE notorm (id varchar(255) NOT NULL, data " . ($driver == "oci" ? "varchar2(4000)" : "text") . " NOT NULL, PRIMARY KEY (id))");
foreach (["empty", "filled"] as $state) {
	$cache = new NotORM\NotORM($connection, cache: new NotORM\Cache\Database($connection));
	$applications = $cache->application();
	$applications->fetch()["title"];
	echo "$applications\n";
	$applications->__destruct();
}
$connection->exec("DROP TABLE notorm");
?>
--EXPECTF--
SELECT * FROM application
SELECT id, title FROM application
