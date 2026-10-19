# Tests

`composer test` runs the tests on MySQL, SQLite and PostgreSQL, `composer test sqlite` only on one of them.
MS SQL (`sqlsrv`), Oracle (`oci`) and Firebird (`firebird`) run only when named.
A single test runs by `php tests/1-basic.phpt sqlite`.

Each run recreates the tables from [software.sql](software.sql), so the databases only need to exist.
All servers run on `localhost` with the default port and a user `ODBC` with the password `ODBC`.

## MySQL

```sql
CREATE DATABASE software;
CREATE USER 'ODBC'@'localhost' IDENTIFIED BY 'ODBC';
GRANT ALL PRIVILEGES ON software.* TO 'ODBC'@'localhost';
```

## PostgreSQL

`pg_hba.conf` must allow a password login. PostgreSQL stores the role name as written, so it must be quoted:

```sql
CREATE ROLE "ODBC" LOGIN PASSWORD 'ODBC';
CREATE DATABASE software OWNER "ODBC";
```

## SQLite

Nothing, the tests use an in-memory database.

## MS SQL

SQL Server authentication on port 1433 (SQL Server Express uses a dynamic port by default).
The password doesn't satisfy the password policy:

```sql
CREATE DATABASE software;
CREATE LOGIN ODBC WITH PASSWORD = 'ODBC', CHECK_POLICY = OFF;
USE software;
CREATE USER ODBC FOR LOGIN ODBC;
ALTER ROLE db_owner ADD MEMBER ODBC;
```

## Oracle

Oracle Database Express Edition with the pluggable database `XEPDB1`.
The tables are in the schema `software` without a login, the tests switch to it by `ALTER SESSION SET CURRENT_SCHEMA`.
In `XEPDB1`:

```sql
CREATE USER ODBC IDENTIFIED BY "ODBC";
GRANT DBA TO ODBC;
CREATE USER software NO AUTHENTICATION;
GRANT UNLIMITED TABLESPACE TO software;
```

## Firebird

Add an alias to `databases.conf`, e.g. `software = /data/software.fdb`, then in `isql` connected as `SYSDBA`:

```sql
CREATE USER ODBC PASSWORD 'ODBC';
CREATE DATABASE 'localhost:software' USER 'ODBC' PASSWORD 'ODBC';
```

## Coverage

[coverage.php](coverage.php) prints the coverage of each file in `NotORM/` with the lines the tests didn't execute.
It needs Xdebug and runs the tests on one database, because the run on all databases starts the other processes without Xdebug:

```sh
php -d zend_extension=xdebug -d xdebug.mode=coverage -d auto_prepend_file=tests/coverage.php tests/run-tests.php sqlite
```
