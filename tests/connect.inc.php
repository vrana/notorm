<?php
error_reporting(E_ALL | E_STRICT);
include "../NotORM.php";

$connection = new PDO("mysql:host=127.0.0.1;dbname=software", "ODBC");
//~ $connection = new PDO("sqlite:software.sdb");
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
/*
include "dibi.php";
$connection = new DibiConnection(array('database' => 'software'));
//~ $connection = new DibiConnection(array('driver' => 'sqlite3', 'database' => 'software.sdb'));
*/
$software = new NotORM($connection); //~ , new NotORM_Structure_Discovery($connection)
