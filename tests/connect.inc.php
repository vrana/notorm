<?php
error_reporting(E_ALL | E_STRICT);
require dirname(__FILE__) . "/../vendor/autoload.php";

$connection = new PDO("mysql:dbname=software", "ODBC", "ODBC");
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
$software = new NotORM\NotORM($connection);
//~ $software->debug = true;
