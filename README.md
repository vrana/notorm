# NotORM - http://www.notorm.com/

NotORM is a PHP library for simple working with data in the database. The most interesting feature is a very easy work with table relationships. The overall performance is also very important and NotORM can actually run faster than a native driver.

Requirements:
PHP 5.3+
any database supported by PDO 

Usage:
<?php
require __DIR__ . '/vendor/autoload.php';
$connection = new PDO("mysql:dbname=software");
$software = new NotORM\Instance($connection);

foreach ($software->application()->order("title") as $application) { // get all applications ordered by title
    echo "$application[title]\n"; // print application title
    echo $application->author["name"] . "\n"; // print name of the application author
    foreach ($application->application_tag() as $application_tag) { // get all tags of $application
        echo $application_tag->tag["name"] . "\n"; // print the tag name
    }
}
?>
[![Build Status](https://secure.travis-ci.org/sim2github/notorm.png)](http://travis-ci.org/sim2github/notorm)