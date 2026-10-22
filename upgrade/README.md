# Upgrading from NotORM 1
NotORM 2 moved the classes to the namespace `NotORM` and declares native types. `upgrade.php` rewrites code using NotORM 1 for it:

```
php vendor/vrana/notorm/upgrade/upgrade.php [--dry-run] path...
```

The paths are files or directories searched for `*.php` and `*.phtml` (except `vendor` and `.git`). The files are rewritten in place, so commit them first and review the diff. `--dry-run` only prints what would change.

The script uses [PHP-Parser](https://github.com/nikic/PHP-Parser), which a plain `composer install` of NotORM doesn't install. The script installs it to `upgrade/vendor` by `composer install` on the first run, or install it yourself with `composer install --working-dir=vendor/vrana/notorm/upgrade`.

## What it changes
- Class names: `NotORM` → `\NotORM\NotORM`, `NotORM_Result` → `\NotORM\Result` and similarly the other classes, `NotORM_Abstract` → `\NotORM\Base`, `NotORM_Cache_Include` → `\NotORM\Cache\IncludeFile`, `NotORM_Cache_APC` → `\NotORM\Cache\APCu`. The names are fully qualified, so they work in any namespace. The same is done in strings consisting of a class name (e.g. `$db->rowClass = 'NotORM_Row'`) and in comments. `use NotORM_Row;` is removed, `use NotORM_Result as Result;` is changed to `use NotORM\Result;`.
- Method names: `insert_multi()` → `insertMulti()`, `insert_update()` → `insertUpdate()`, `insert_id()` → `insertId()`. The calls are renamed on any object, so check the reported ones.
- Classes extending or implementing NotORM classes (custom rows, structures, caches), also through other classes: the overridden methods get the return types and the redeclared properties get the types declared by NotORM, e.g. `function getReferencedTable($name, $table): string`. `$this->primary` in a row class is renamed to `$this->id` (the value of the primary key).
- `fetch()` returns `null` instead of `false` after the last row: `=== false` and `!== false` are changed to `null` if the `fetch()` is surely NotORM's (called after `where()`, `order()` and similar, on an object created by `new NotORM\…`, or with a column name). PDO's `fetch()` is left alone and the comparisons the script isn't sure about are reported.
- An array offset of a result throws because `$db->table[$_GET['id']]` allowed SQL injection: `$db->table[["title" => $title]]` is changed to `$db->table->where(["title" => $title])->fetch()`, also with a variable assigned only arrays.

## What it reports
- `include "NotORM.php"` - the classes are loaded by Composer's `require "vendor/autoload.php"`.
- Methods in the classes extending NotORM classes which don't accept the parameters of the NotORM method, e.g. `function update($data)` instead of `function update($data = null)`.
- Comparisons of `fetch()` with `false` which can't be decided.
- Renamed method calls.
- Array offsets of a stored result (`where()` would change it), in `isset()` and assigned to.

## What to check yourself
- NotORM 2 requires PHP 8.0+.
- `group()` without `select()` selects the grouped columns instead of `*`.
- An undefined method of `NotORM\Result` throws PHP's `Error`.
- `$db->table[$id]` throws if `$id` is an array, e.g. `$_GET['id']` from `?id[]=1`.
- `insertUpdate()` returns `false` on an error.
- The cached columns are stored under different keys, so the cache is built again.
