[![Build Status](https://secure.travis-ci.org/sim2github/notorm.png)](http://travis-ci.org/sim2github/notorm) [![Code Coverage](https://scrutinizer-ci.com/g/sim2github/notorm/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sim2github/notorm/?branch=master)

# NotORM - http://www.notorm.com/

NotORM is a PHP library for simple working with data in the database. The most interesting feature is a very easy work with table relationships. The overall performance is also very important and NotORM can actually run faster than a native driver.

### Requirements
* PHP 5.4+
* any database supported by PDO 

### Install via Composer

If you do not have [Composer](http://getcomposer.org/), you may install it by following the instructions
at [getcomposer.org](http://getcomposer.org/doc/00-intro.md#installation-nix).

Your *composer.json* must have minimum this lines
```json
{
    "name": "notorm/test",
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/sim2github/notorm.git"
        }
    ],
    "require": {
        "vrana/notorm": "dev-master"
    },
    "minimum-stability": "dev"
}
```
Use command ```php composer.phar install``` to install all dependencies.

### Usage
```php
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
```
More examples in [official site](http://www.notorm.com/) or in ***tests*** folder.

### Tests
If you do not have [PHPUnit](https://phpunit.de/) you can install it by folowing this [instruction](https://phpunit.de/manual/current/en/installation.html).

Run PHPUnit in the NotORM repo base directory.

```
phpunit
```

You can run tests for specific groups only:

``` phpunit --group=mysql,sqlite ``` or ``` phpunit --exclude-group=pgsql ```

You can get a list of available groups via `phpunit --list-groups`.

A single test class could be run like the follwing:

```
phpunit tests/MysqlTest.php
```

#### Configuration tests
PHPUnit configuration is in `phpunit.xml.dist` in repository root folder.
You can create your own phpunit.xml to override dist config.

You can override configuration values by creating a `config.local.php` file
and manipulate the `$config` variable.
For example to change MySQL username and password your `config.local.php` should
contain the following:

```php
<?php
$config['databases']['mysql']['username'] = 'root';
$config['databases']['mysql']['password'] = 'root';
```

