# NotORM
**NotORM** is a PHP library for simple working with data in the database. The most interesting feature is a very easy work with table relationships. The overall performance is also very important and NotORM can actually run faster than a native driver.

[Official Website](https://www.notorm.com/)

## Requirements
- PHP 8.0+
- Any database supported by PDO (tested with MySQL, SQLite, PostgreSQL, MS SQL, Oracle)

## Installation
```
composer require vrana/notorm
```

Upgrading from NotORM 1: see [upgrade/README.md](upgrade/README.md).

## Usage
```php
<?php
require "vendor/autoload.php";
$pdo = new PDO("mysql:dbname=software");
$db = new NotORM\NotORM($pdo);

foreach ($db->application()->order("title") as $application) { // get all applications ordered by title
    echo "$application[title]\n"; // print application title
    echo $application->author["name"] . "\n"; // print name of the application author
    foreach ($application->application_tag() as $application_tag) { // get all tags of $application
        echo $application_tag->tag["name"] . "\n"; // print the tag name
    }
}
```

## Development
- `composer check` - Checks the code by PHP_CodeSniffer and PHPStan
- `composer test` - Runs the tests, see [tests/README.md](tests/README.md) for setting up the databases
