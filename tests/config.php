<?php

/**
 * This is the configuration file for the NotORM unit tests.
 * You can override configuration values by creating a `config.local.php` file
 * and manipulate the `$config` variable.
 * For example to change MySQL username and password your `config.local.php` should
 * contain the following:
 *
<?php
$config['databases']['mysql']['username'] = 'software';
$config['databases']['mysql']['password'] = 'changeme';
 */

$config = [
	'databases' => [
		'mysql' => [
			'dsn' => 'mysql:host=127.0.0.1;dbname=software',
			'username' => 'travis',
			'password' => '',
			'fixture' => __DIR__ . '/fixtures/mysql.sql',
		],
		'sqlite' => [
			'dsn' => 'sqlite::memory:',
			'fixture' => __DIR__ . '/fixtures/sqlite.sql',
		],
		'pgsql' => [
			'dsn' => 'pgsql:host=localhost;dbname=software;port=5432;',
			'username' => 'postgres',
			'password' => 'postgres',
			'fixture' => __DIR__ . '/fixtures/postgres.sql',
		],
		'sqlsrv' => [
			'dsn' => 'sqlsrv:Server=localhost;Database=test',
			'username' => '',
			'password' => '',
			'fixture' => __DIR__ . '/fixtures/mssql.sql',
		],
	],
];
if (is_file(__DIR__ . '/config.local.php')) {
	include(__DIR__ . '/config.local.php');
}

return $config;
