<?php
error_reporting(E_ALL | E_STRICT);
include "../NotORM.php";

$connection = new PDO("mysql:host=127.0.0.1;dbname=software", "ODBC");
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
/*
include 'dibi.php';
$connection = new DibiConnection(array('database' => 'software'));
*/
$software = new NotORM($connection); //~ , new NotORM_Structure_Discovery($connection)
