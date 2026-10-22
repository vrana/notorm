<?php
include "notorm/NotORM.php";
include "SoftwareConvention.php";

$pdo = new PDO("mysql:dbname=software");
$cache = new NotORM_Cache_Include("notorm.cache.php");
$software = new NotORM($pdo, new SoftwareConvention, $cache);
$software->rowClass = 'App\ApplicationRow';

/** @var NotORM_Result $applications */
$applications = $software->application()->order("title");
while (($application = $applications->fetch()) !== false) { // NotORM_Row
	echo "$application[title]\n";
}

if ($software->application()->where("title", "Adminer")->fetch() === false) {
	$software->application()->insert_update(["title" => "Adminer"], []);
	echo $software->application()->insert_id() . "\n";
}

$title = $software->application("id", 1)->fetch("title");
if ($title === false) {
	echo "missing\n";
}

$statement = $pdo->query("SELECT * FROM application");
while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
	echo "$row[title]\n";
}

if ($software->author[1] instanceof NotORM_Row) {
	echo get_class(new NotORM_Literal("NOW()")) . "\n";
}

function firstTitle($result) {
	$row = $result->fetch();
	return ($row !== false ? $row["title"] : "");
}

$adminer = $software->application[["title" => "Adminer"]];
echo $software->application[array("title" => "Adminer", "web" => new NotORM_Literal("web"))]["id"] . "\n";
$where = ["author_id" => 11, "maintainer_id" => null];
echo $software->application[$where]["id"] . "\n";
if (isset($software->application[$where])) {
	echo $applications[$where]["id"] . "\n";
}
echo $software->application[$_GET["id"]]["title"] . "\n";
