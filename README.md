NotORM
=============

NotORM is a PHP library for simple working with data in the database. The most interesting feature is a very easy work with table relationships. The overall performance is also very important and NotORM can actually run faster than a native driver.

## Requirements
 * PHP 5.1+
 * any database supported by PDO (tested with MySQL, SQLite, PostgreSQL, MS SQL, Oracle)

## Installation
Download this library as a ZIP or using [Composer](http://getcomposer.org/):
```
$ php composer.phar require vrana/notorm
```

## Usage
```php
<?php

require_once 'NotORM.php';
$PDO = new PDO('mysql:dbname=software');
$software = new NotORM($PDO);

// get all applications ordered by title
foreach ($software->application()->order('title') as $application) {
	// print application title
    echo $application['title'] . PHP_EOL;
    // print name of the application author
    echo $application->author['name'] . PHP_EOL;
    // get all tags of $application
    foreach ($application->application_tag() as $application_tag) {
    	// print the tag name
        echo $application_tag->tag['name'] . PHP_EOL;
    }
}
```

## To Do
 * multi-column primary key - Structure methods could return array
 * Discovery for other drivers
 * defer NotORM_Row creation to save memory