NotORM - http://www.notorm.com/

NotORM is a PHP library for simple reading data from the database. The most interesting feature is a very easy work with table relations. The overall performance is also very important and NotORM can actually run faster than a native driver.

Requirements:
PHP 5.1+
any database supported by PDO (tested with MySQL, SQLite, PostgreSQL, MS SQL)

Usage:
<?php
include "NotORM.php";
$pdo = new PDO("mysql:dbname=software");
$software = new NotORM($pdo);

foreach ($software->application()->order("title") as $application) { // get all applications ordered by title
    echo "$application[title]\n"; // print application title
    echo $application->author["name"] . "\n"; // print name of the application author
    foreach ($application->application_tag() as $application_tag) { // get all tags of $application
        echo $application_tag->tag["name"] . "\n"; // print the tag name
    }
}
?>
