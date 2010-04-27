<?php
error_reporting(E_ALL | E_STRICT);
include "../NotORM.php";

$pdo = new PDO("mysql:host=127.0.0.1;dbname=software", "ODBC");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$software = new NotORM($pdo); //~ , new NotORM_Structure_Discovery($pdo, "mysql");
