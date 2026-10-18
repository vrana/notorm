# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

NotORM is a PHP library for working with a PDO database, centred on easy traversal of table relationships with a minimal number of queries. All classes are in the `NotORM` namespace, one class per file under `NotORM/`, autoloaded by Composer (PSR-4). The entry point is `NotORM\NotORM`.

The minimum is **PHP 8.0**, so there are no `readonly`, `never`, intersection or standalone `null`/`false` types. Follow the existing idiom, which `phpcs.xml` enforces: `<?php declare(strict_types=1);` on the first line, `[]` not `array()`, tabs, `function` without visibility, native types on every parameter, return value and property, and doc-comments only for what the native type can't say (array items, `$this`, `callable`, a description). Global classes are written with a leading backslash (`\PDO`, `\Traversable`), not imported with `use`. Strict types apply only to the calls the library makes, so a `Row`, `Result` or `Literal` passed where a string is expected needs an explicit `(string)`; the applications' calls into the library keep their own mode.

## Commands

```bash
composer install
```

```bash
composer check
```

```bash
composer test
```

- `composer install` installs PHP_CodeSniffer and PHPStan and generates `vendor/autoload.php`, which the tests need. `"lock": false` means no `composer.lock` is ever written.
- `composer check` runs `phpcs` (config `phpcs.xml`) and `phpstan analyse` (config `phpstan.neon`, level 1 with the strict rules that currently report nothing). Both configs list the rules the code doesn't follow and why; keep them passing without adding new exclusions where a small fix will do.
- `composer test` runs `tests/run-tests.php`, which runs the tests on MySQL, in-memory SQLite and PostgreSQL, each in its own process; `composer test sqlite` runs them only on one database, `composer test sqlsrv` on MS SQL, which isn't in the default run (the local SQL Server needs the same `software` database, PHP needs `pdo_sqlsrv`). `tests/connect.inc.php` takes the database from the first command-line argument (`mysql` by default), also for a single test: `php tests/1-basic.phpt sqlite`. Run the tests with PHP 8.0 and the latest PHP.

## Tests

`tests/connect.inc.php` recreates the tables from `tests/software.sql` on every run, so MySQL and PostgreSQL only need an empty database `software` accessible with the credentials there.

- The runner `require`s every `tests/*.phpt` in natural order **in one PHP process**, each in its own function scope: each file is plain PHP that echoes its literal `--TEST--`/`--EXPECTF--` text, and the runner compares the `--FILE--` output to the `--EXPECTF--` section **byte-for-byte** (no `%s`-style placeholders despite the name). It prints only `failed …`/`skipped …` lines plus the total time and memory; no other output means all passed.
- The runner requires `connect.inc.php` first, so the tests' `require_once` doesn't run it again, and the tests get its `$connection`, `$software` and `$driver` as globals; their other variables don't leak to the other tests. The objects are shared, so a test that changes `$software` or `$connection` must restore them. A `--SKIPIF--` section that echoes something skips the test (used for driver-specific tests).
- To run one test, execute it directly and compare its printed output against its `--EXPECTF--` block by eye: `php tests/1-basic.phpt`.
- Tests that write (`10-update`, `22-insert-update`, …) must leave the fixture data as they found it, since later tests depend on it.
- Many tests assert generated SQL by echoing a result object (`echo "$applications"` → `NotORM\Result::__toString()`); expected output therefore pins exact query text. Without `ORDER BY` the row order is not guaranteed.
- New tests take the next free number: `tests/NN-name.phpt`.

## Architecture

- **`NotORM\NotORM`**: holds the PDO connection, driver name, structure, and cache. `$db->table()` / `$db->table("col", $val)` returns a `NotORM\Result`; `$db->table[$id]` uses `__get` with a "single" result that queries only the requested row. Write-only flags are set through `__set` (`debug`, `debugTimer`, `freeze`, `rowClass`, `jsonAsArray`, `transaction`).
- **Friend-visibility emulation**: every class extends `NotORM\Base`, which declares the shared `protected` properties (`$notORM`, `$table`, `$primary`, `$rows`, `$referenced`, …) and `access()`. This lets `Row`, `Result`, and `MultiResult` read each other's protected state (e.g. `$this->result->notORM->structure`). New cross-class state belongs there; don't make it public.
- **`NotORM\Result`**: a lazy query builder. `where/select/order/limit/group/union/lock` accumulate clauses; `__toString()` builds the SQL; `execute()` runs it on first iteration/array access and stores rows keyed by primary key. `where()` handles scalar, `null`, array (`IN`), nested `Result` (subquery, or prefetched `IN` list on MySQL), and array-of-conditions forms; `and()`/`or()` add a condition with that operator, and a condition consisting only of `)` closes a group opened with `"(col"`.
  - Without `select()`, `group()` selects the grouped columns instead of `*` (anything else is invalid with `ONLY_FULL_GROUP_BY` and in other databases), and such results bypass the column cache. `MultiResult` adds its referencing column to both its select and its GROUP BY.
  - Dotted/colon names in conditions, select, order, and group (`author.name`, `application_tag:tag_id`) are turned into `LEFT JOIN`s by `createJoins()`; `.` follows a referenced (belongs-to) relation, `:` a referencing (back) relation.
  - Driver differences are branches on `$this->notORM->driver` (`mysql`, `sqlite`, `pgsql`, `oci`, `dblib`/`mssql`/`sqlsrv`): `LIMIT` vs `TOP` or `OFFSET … FETCH` vs `rownum`, UNION parenthesisation or derived tables, `insertUpdate` (MySQL `ON DUPLICATE KEY` vs insert-then-update on SQLSTATE 23000/23505), empty insert.
  - Values are inlined with `quote()` (not bound) for inserts, updates, and `IN` lists; `?`/`:name` placeholders in conditions and `NotORM\Literal` parameters are collected into `$parameters` and bound. `Literal` is raw SQL.
  - Statements NotORM iterates set their fetch mode explicitly, so the connection's `PDO::ATTR_DEFAULT_FETCH_MODE` must not matter.
  - `freeze` makes all writes return `false` without querying.
- **Relationship loading (no N+1)**, the key design:
  - `$row->author` (`Row::__get`): on first access, collects the foreign key of *every* row in the parent result and loads all referenced rows in one `IN` query, cached in the parent result's `$referenced[$name]`.
  - `$row->application_tag()` (`Row::__call`) returns a `NotORM\MultiResult` filtered by all parent row IDs. Its `execute()` runs one query, groups rows by the referencing column, and caches the grouping in the parent result's `$referencing[<SQL>]`; each MultiResult then exposes only its own group (`$active`). With `limit()`, it instead builds a UNION of per-parent queries. `via()` overrides the referencing column; `update/delete/aggregation` are narrowed to the single parent via `single()`.
- **`NotORM\Structure`** maps names to tables/columns. `Structure\Convention` (default: primary `id`, foreign `%s_id`, table `%s`, optional prefix) is pure string formatting; `Structure\Discovery` reads the primary and foreign keys from the database metadata and can cache what it learns. The queries are in a `Structure\Discovery\Driver` chosen by the PDO driver name (`MySQL`, `SQLite`, `PostgreSQL`, `MSSQL`, `Oracle`; single-column keys only).
- **`NotORM\Cache`** (implementations in `NotORM\Cache\`: `Session`, `File`, `IncludeFile`, `Database`, `Memcache`, `Memcached`, `APCu`): used to remember which columns were actually accessed for each `"table;conditions"` key. `Result::__destruct()` saves the access map; next time `__toString()` selects only those columns. If a row then reads a column not in the cached list, `Row::access()` → `Result::access()` invalidates and re-runs the query with `*`. Clause-changing methods call `$this->__destruct()` to flush/reset this state.
- **`NotORM\Row`**: `ArrayAccess` over columns, tracks `$modified` for `update()` with no argument; `__toString()` is the primary key value, so a row can be passed directly as a foreign-key value. Custom row classes are set via `$db->rowClass`.

## Repository conventions

- Commit subjects are short imperative sentences, prefixed with a group where one fits (`Tests: …`, `PHPStan: …`, `MultiResult: …`), with `(fixes #N)` / `(closes #N)` when tied to a GitHub issue.
- `todo.txt` lists known open ideas (multi-column primary keys).
