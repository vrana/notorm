<?php
error_reporting(E_ALL | E_STRICT);
include dirname(__FILE__) . "/../NotORM.php";
include "dibi.php";

//~ $connection = new DibiConnection(array('database' => 'software'));
$connection = new DibiConnection(array('driver' => 'sqlite3', 'database' => 'software.sdb'));
$software = new NotORM($connection);
