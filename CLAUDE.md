# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

NotORM is a dependency-free PHP library for working with a PDO database, centred on easy traversal of table relationships with a minimal number of queries. The entry point is `NotORM.php`, which `include_once`s everything in `NotORM/` (Composer autoloads it via `"files"`). There is no build step, linter, or Composer dev dependency.

Compatibility target is **PHP 5.1+** (see `readme.txt`, the `JsonSerializable` polyfill in `NotORM.php`). Keep to that idiom: `array()` not `[]`, `dirname(__FILE__)` not `__DIR__`, no namespaces, closures, `??`, type declarations beyond class/array hints, or other newer syntax. Code is indented with tabs.

## Tests

Tests need a MySQL database `software` loaded from `tests/software.sql`, reachable as user `ODBC` with no password (see `tests/connect.inc.php`; edit locally if your setup differs).

```bash
php tests/run-tests.php
```

- The runner `include`s every `tests/*.phpt` in natural order **in one PHP process**: each file is plain PHP that echoes its literal `--TEST--`/`--EXPECTF--` text, and the runner compares the `--FILE--` output to the `--EXPECTF--` section **byte-for-byte** (no `%s`-style placeholders despite the name). It prints only `failed …`/`skipped …` lines plus the total time and memory; no other output means all passed.
- Because files are included into the same scope, `$connection` and `$software` from `connect.inc.php` (pulled in with `include_once`) are shared across tests. A `--SKIPIF--` section that echoes something skips the test (used for driver-specific tests).
- To run one test, execute it directly and compare its printed output against its `--EXPECTF--` block by eye: `php tests/1-basic.phpt`.
- Tests that write (`10-update`, `22-insert-update`, …) must leave the fixture data as they found it, since later tests depend on it.
- Many tests assert generated SQL by echoing a result object (`echo "$applications"` → `NotORM_Result::__toString()`); expected output therefore pins exact query text.
- New tests take the next free number: `tests/NN-name.phpt`.

## Architecture

- **`NotORM`** (`NotORM.php`): holds the PDO connection, driver name, structure, and cache. `$db->table()` / `$db->table("col", $val)` returns a `NotORM_Result`; `$db->table[$id]` uses `__get` with a "single" result that queries only the requested row. Write-only flags are set through `__set` (`debug`, `debugTimer`, `freeze`, `rowClass`, `jsonAsArray`, `transaction`).
- **Friend-visibility emulation**: every class extends `NotORM_Abstract`, which declares the shared `protected` properties (`$notORM`, `$table`, `$primary`, `$rows`, `$referenced`, …) and `access()`. This lets `Row`, `Result`, and `MultiResult` read each other's protected state (e.g. `$this->result->notORM->structure`). New cross-class state belongs there; don't make it public.
- **`NotORM_Result`** (`NotORM/Result.php`): a lazy query builder. `where/select/order/limit/group/union/lock` accumulate clauses; `__toString()` builds the SQL; `execute()` runs it on first iteration/array access and stores rows keyed by primary key. `where()` handles scalar, `null`, array (`IN`), nested `NotORM_Result` (subquery, or prefetched `IN` list on MySQL), and array-of-conditions forms; `and()`/`or()` come via `__call`, and a condition consisting only of `)` closes a group opened with `"(col"`.
  - Dotted/colon names in conditions, select, order, and group (`author.name`, `application_tag:tag_id`) are turned into `LEFT JOIN`s by `createJoins()`; `.` follows a referenced (belongs-to) relation, `:` a referencing (back) relation.
  - Driver differences are branches on `$this->notORM->driver` (`mysql`, `sqlite`, `pgsql`, `oci`, `dblib`/`mssql`/`sqlsrv`): `LIMIT` vs `TOP` vs `rownum`, UNION parenthesisation, `insert_update` (MySQL `ON DUPLICATE KEY` vs insert-then-update on SQLSTATE 23000/23505), empty insert.
  - Values are inlined with `quote()` (not bound) for inserts, updates, and `IN` lists; `?`/`:name` placeholders in conditions and `NotORM_Literal` parameters are collected into `$parameters` and bound. `NotORM_Literal` is raw SQL.
  - `freeze` makes all writes return `false` without querying.
- **Relationship loading (no N+1)** — the key design:
  - `$row->author` (`NotORM_Row::__get`): on first access, collects the foreign key of *every* row in the parent result and loads all referenced rows in one `IN` query, cached in the parent result's `$referenced[$name]`.
  - `$row->application_tag()` (`NotORM_Row::__call`) returns a `NotORM_MultiResult` filtered by all parent row IDs. Its `execute()` runs one query, groups rows by the referencing column, and caches the grouping in the parent result's `$referencing[<SQL>]`; each MultiResult then exposes only its own group (`$active`). With `limit()`, it instead builds a UNION of per-parent queries. `via()` overrides the referencing column; `update/delete/aggregation` are narrowed to the single parent via `single()`.
- **`NotORM_Structure`** (`NotORM/Structure.php`) maps names to tables/columns. `NotORM_Structure_Convention` (default: primary `id`, foreign `%s_id`, table `%s`, optional prefix) is pure string formatting; `NotORM_Structure_Discovery` reads MySQL `information_schema`/`EXPLAIN` only and can cache what it learns.
- **`NotORM_Cache`** (`NotORM/Cache.php`, implementations: Session, File, Include, Database, Memcache, APC): used to remember which columns were actually accessed for each `"table;conditions"` key. `Result::__destruct()` saves the access map; next time `__toString()` selects only those columns. If a row then reads a column not in the cached list, `Row::access()` → `Result::access()` invalidates and re-runs the query with `*`. Clause-changing methods call `$this->__destruct()` to flush/reset this state.
- **`NotORM_Row`** (`NotORM/Row.php`): `ArrayAccess` over columns, tracks `$modified` for `update()` with no argument; `__toString()` is the primary key value, so a row can be passed directly as a foreign-key value. Custom row classes are set via `$db->rowClass`.

## Repository conventions

- Commit subjects are short imperative sentences, with `(fixes #N)` / `(closes #N)` when tied to a GitHub issue.
- `todo.txt` lists known open ideas (multi-column primary keys, discovery for non-MySQL drivers, deferred row creation).
