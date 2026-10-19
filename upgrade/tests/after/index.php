<?php
include "notorm/NotORM.php";
include "SoftwareConvention.php";

$pdo = new PDO("mysql:dbname=software");
$cache = new \NotORM\Cache\IncludeFile("notorm.cache.php");
$software = new \NotORM\NotORM($pdo, new SoftwareConvention, $cache);
$software->rowClass = 'App\ApplicationRow';

/** @var \NotORM\Result $applications */
$applications = $software->application()->order("title");
while (($application = $applications->fetch()) !== null) { // \NotORM\Row
	echo "$application[title]\n";
}

if ($software->application()->where("title", "Adminer")->fetch() === null) {
	$software->application()->insertUpdate(["title" => "Adminer"], []);
	echo $software->application()->insertId() . "\n";
}

$title = $software->application("id", 1)->fetch("title");
if ($title === null) {
	echo "missing\n";
}

$statement = $pdo->query("SELECT * FROM application");
while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
	echo "$row[title]\n";
}

if ($software->author[1] instanceof \NotORM\Row) {
	echo get_class(new \NotORM\Literal("NOW()")) . "\n";
}

function firstTitle($result) {
	$row = $result->fetch();
	return ($row !== false ? $row["title"] : "");
}
