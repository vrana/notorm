<?php declare(strict_types=1);

/** Upgrade code using NotORM 1 (classes NotORM_*) to NotORM 2 (namespace NotORM)
* Usage: php upgrade.php [--dry-run] path...
* @link https://www.notorm.com/
*/

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;

/** NotORM 1 class names (lowercase) => NotORM 2 class names */
const UPGRADE_CLASSES = [
	'notorm' => 'NotORM\NotORM',
	'notorm_abstract' => 'NotORM\Base',
	'notorm_result' => 'NotORM\Result',
	'notorm_multiresult' => 'NotORM\MultiResult',
	'notorm_row' => 'NotORM\Row',
	'notorm_literal' => 'NotORM\Literal',
	'notorm_structure' => 'NotORM\Structure',
	'notorm_structure_convention' => 'NotORM\Structure\Convention',
	'notorm_structure_discovery' => 'NotORM\Structure\Discovery',
	'notorm_cache' => 'NotORM\Cache',
	'notorm_cache_session' => 'NotORM\Cache\Session',
	'notorm_cache_file' => 'NotORM\Cache\File',
	'notorm_cache_include' => 'NotORM\Cache\IncludeFile',
	'notorm_cache_database' => 'NotORM\Cache\Database',
	'notorm_cache_memcache' => 'NotORM\Cache\Memcache',
	'notorm_cache_apc' => 'NotORM\Cache\APCu',
];

/** NotORM 1 method names => NotORM 2 method names */
const UPGRADE_METHODS = [
	'insert_multi' => 'insertMulti',
	'insert_update' => 'insertUpdate',
	'insert_id' => 'insertId',
];

/** Methods of NotORM\Result which PDOStatement doesn't have, fetch() after them is NotORM's */
const UPGRADE_RESULT_METHODS = ['where', 'and', 'or', 'select', 'order', 'limit', 'group', 'union', 'lock', 'via'];

/** Methods returning PDOStatement, fetch() after them is PDO's */
const UPGRADE_PDO_METHODS = ['query', 'prepare'];

/** Upgrade files
* @param list<string> $paths files or directories searched for *.php and *.phtml
* @param bool $write false to only report what would change
* @return list<string> messages
* @throws RuntimeException if the parser can't be installed
*/
function upgrade(array $paths, bool $write = true): array {
	upgradeAutoload();
	$parser = (new ParserFactory)->createForNewestSupportedVersion();
	$traverser = new NodeTraverser(new ParentConnectingVisitor, new NameResolver(options: ['replaceNodes' => false]));
	$messages = [];
	$files = [];
	foreach (upgradeFilenames($paths, $messages) as $filename) {
		$code = file_get_contents($filename);
		try {
			$ast = $parser->parse($code);
		} catch (PhpParser\Error $e) {
			$messages[] = "$filename:" . $e->getStartLine() . ": error: " . $e->getRawMessage() . ", skipped";
			continue;
		}
		$files[$filename] = [$code, $traverser->traverse($ast), $parser->getTokens()];
	}

	// classes of all files to find the descendants of NotORM classes also through other classes
	$classes = [];
	foreach ($files as [, $ast]) {
		foreach ((new NodeFinder)->findInstanceOf($ast, Stmt\ClassLike::class) as $class) {
			if ($class->namespacedName) {
				$classes[$class->namespacedName->toLowerString()] = upgradeParents($class);
			}
		}
	}

	foreach ($files as $filename => [$code, $ast, $tokens]) {
		[$upgraded, $lines] = upgradeCode($code, $ast, $tokens, $classes);
		if ($upgraded !== $code) {
			$messages[] = "$filename: " . ($write ? "upgraded" : "would be upgraded");
			if ($write) {
				file_put_contents($filename, $upgraded);
			}
		}
		foreach ($lines as [$line, $message]) {
			$messages[] = "$filename:$line: $message";
		}
	}
	return $messages;
}

/** Load PHP-Parser, install it if it's missing; load NotORM 2 classes
* @throws RuntimeException
*/
function upgradeAutoload(): void {
	if (!class_exists(ParserFactory::class)) {
		$autoload = __DIR__ . "/vendor/autoload.php";
		if (!file_exists($autoload)) {
			passthru("composer install --no-interaction --working-dir=" . escapeshellarg(__DIR__), $exitCode);
			if ($exitCode || !file_exists($autoload)) {
				throw new RuntimeException("Install the parser by: composer install --working-dir=" . escapeshellarg(__DIR__));
			}
		}
		require $autoload;
	}
	if (!class_exists(NotORM\NotORM::class)) {
		spl_autoload_register(function (string $class): void {
			$filename = __DIR__ . "/../" . str_replace("\\", "/", $class) . ".php";
			if (str_starts_with($class, "NotORM\\") && file_exists($filename)) {
				require $filename;
			}
		});
	}
}

/** Get files to upgrade
* @param list<string> $paths
* @param list<string> $messages
* @return list<string>
*/
function upgradeFilenames(array $paths, array &$messages): array {
	$return = [];
	foreach ($paths as $path) {
		if (is_dir($path)) {
			$iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
				fn (SplFileInfo $file): bool => ($file->isDir()
					? !in_array($file->getFilename(), ['vendor', '.git'], true)
					: preg_match('~\.(php|phtml)$~i', $file->getFilename()) === 1
				),
			));
			$filenames = [];
			foreach ($iterator as $filename => $file) {
				$filenames[] = str_replace(DIRECTORY_SEPARATOR, "/", $filename);
			}
			natsort($filenames);
			array_push($return, ...$filenames);
		} elseif (is_file($path)) {
			$return[] = $path;
		} else {
			$messages[] = "$path: error: not found";
		}
	}
	return $return;
}

/** Get NotORM 2 name of a class
* @return string resolved name without leading backslash
*/
function upgradeName(Name $name): string {
	$resolved = ($name->getAttribute('resolvedName') ?? $name)->toString();
	return UPGRADE_CLASSES[strtolower($resolved)] ?? $resolved;
}

/** Get parent class and interfaces
* @return list<string>
*/
function upgradeParents(Stmt\ClassLike $class): array {
	$names = match (true) {
		$class instanceof Stmt\Class_ => [...($class->extends ? [$class->extends] : []), ...$class->implements],
		$class instanceof Stmt\Interface_ => $class->extends,
		$class instanceof Stmt\Enum_ => $class->implements,
		default => [],
	};
	return array_map(fn (Name $name): string => upgradeName($name), $names);
}

/** Get NotORM classes and interfaces which a class extends or implements
* @param list<string> $names parents
* @param array<string, list<string>> $classes [lowercase class name => parents]
* @param array<string, bool> $seen
* @return list<ReflectionClass<object>>
*/
function upgradeAncestors(array $names, array $classes, array &$seen = []): array {
	$return = [];
	foreach ($names as $name) {
		$key = strtolower($name);
		if (!isset($seen[$key])) {
			$seen[$key] = true;
			if (isset($classes[$key])) {
				array_push($return, ...upgradeAncestors($classes[$key], $classes, $seen));
			} elseif (str_starts_with($key, "notorm\\") && (class_exists($name) || interface_exists($name))) {
				$return[] = new ReflectionClass($name);
			}
		}
	}
	return $return;
}

/** Upgrade code of one file
* @param Node[] $ast
* @param Token[] $tokens
* @param array<string, list<string>> $classes
* @return array{string, list<array{int, string}>} [upgraded code, [line, message]]
*/
function upgradeCode(string $code, array $ast, array $tokens, array $classes): array {
	$finder = new NodeFinder;
	$edits = []; // [start, end, replacement]
	$messages = [];
	$replace = function (Node $node, string $replacement) use (&$edits): void {
		$edits[] = [$node->getStartFilePos(), $node->getEndFilePos() + 1, $replacement];
	};
	$insert = function (int $pos, string $text) use (&$edits): void {
		$edits[] = [$pos, $pos, $text];
	};

	// use statements: an alias is kept, the names used without it are replaced by fully qualified names
	$aliases = [];
	foreach ($finder->findInstanceOf($ast, Stmt\Use_::class) as $use) {
		if ($use->type === Stmt\Use_::TYPE_NORMAL) {
			$old = array_filter($use->uses, fn (Node\UseItem $item): bool => isset(UPGRADE_CLASSES[$item->name->toLowerString()]));
			$unaliased = array_filter($old, fn (Node\UseItem $item): bool => !$item->alias);
			if ($unaliased && count($unaliased) === count($use->uses)) {
				$end = $use->getEndFilePos() + 1;
				$end += strspn($code, "\r\n", $end, 2) - (substr($code, $end, 2) === "\n\n" ? 1 : 0); // remove also the line end
				$edits[] = [$use->getStartFilePos(), $end, ""];
				continue;
			}
			foreach ($old as $item) {
				$class = UPGRADE_CLASSES[$item->name->toLowerString()];
				if (!$item->alias) {
					$replace($item->name, "$class as $item->name");
				} elseif (strcasecmp($item->alias->toString(), substr(strrchr($class, "\\"), 1)) === 0) {
					$edits[] = [$item->name->getStartFilePos(), $item->alias->getEndFilePos() + 1, $class]; // use NotORM\Result
				} else {
					$replace($item->name, $class);
				}
				if ($item->alias) {
					$aliases[$item->alias->toLowerString()] = true;
				}
			}
		}
	}

	// class names
	$functions = []; // names of functions and constants
	foreach ($finder->find($ast, fn (Node $node): bool => $node instanceof Expr\FuncCall || $node instanceof Expr\ConstFetch) as $node) {
		$functions[spl_object_id($node->name)] = true;
	}
	foreach ($finder->findInstanceOf($ast, Name::class) as $name) {
		$resolved = $name->getAttribute('resolvedName');
		if (
			$resolved
			&& !isset($functions[spl_object_id($name)])
			&& isset(UPGRADE_CLASSES[$resolved->toLowerString()])
			&& ($name->isFullyQualified() || !isset($aliases[strtolower($name->getFirst())]))
		) {
			$replace($name, "\\" . UPGRADE_CLASSES[$resolved->toLowerString()]);
		}
	}

	// strings with class names, e.g. $db->rowClass = 'NotORM_Row'
	foreach ($finder->findInstanceOf($ast, Scalar\String_::class) as $string) {
		$key = strtolower(ltrim($string->value, "\\"));
		if (
			str_starts_with($key, "notorm_")
			&& isset(UPGRADE_CLASSES[$key])
			&& in_array($string->getAttribute('kind'), [Scalar\String_::KIND_SINGLE_QUOTED, Scalar\String_::KIND_DOUBLE_QUOTED], true)
		) {
			$replace($string, "'" . UPGRADE_CLASSES[$key] . "'");
		}
	}

	// class names in comments
	foreach ($tokens as $token) {
		if ($token->is([T_COMMENT, T_DOC_COMMENT])) {
			$text = preg_replace_callback(
				'~\\\\?\bNotORM_\w+~i',
				fn (array $match): string => (isset(UPGRADE_CLASSES[strtolower(ltrim($match[0], "\\"))]) ? "\\" . UPGRADE_CLASSES[strtolower(ltrim($match[0], "\\"))] : $match[0]),
				$token->text,
			);
			if ($text !== $token->text) {
				$edits[] = [$token->pos, $token->getEndPos(), $text];
			}
		}
	}

	// renamed methods
	$calls = $finder->find($ast, fn (Node $node): bool => $node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall || $node instanceof Expr\StaticCall);
	foreach ($calls as $call) {
		if ($call->name instanceof Node\Identifier && isset(UPGRADE_METHODS[$call->name->toLowerString()])) {
			$method = UPGRADE_METHODS[$call->name->toLowerString()];
			$replace($call->name, $method);
			$messages[] = [$call->getStartLine(), "notice: $call->name() renamed to $method(), check that it is called on NotORM"];
		}
	}

	// fetch() returns null instead of false after the last row
	foreach ($finder->find($ast, fn (Node $node): bool => $node instanceof Expr\BinaryOp\Identical || $node instanceof Expr\BinaryOp\NotIdentical) as $comparison) {
		foreach ([[$comparison->left, $comparison->right], [$comparison->right, $comparison->left]] as [$false, $expr]) {
			if ($false instanceof Expr\ConstFetch && $false->name->toLowerString() === 'false') {
				$fetch = upgradeFetch($expr, $ast);
				if ($fetch === 'notorm') {
					$replace($false, 'null');
				} elseif ($fetch === 'unsure') {
					$messages[] = [$comparison->getStartLine(), "warning: fetch() returns null instead of false after the last row, check this comparison"];
				}
			}
		}
	}

	// an array offset of Result isn't supported, PHP arrays don't accept it so it's surely NotORM's
	foreach ($finder->findInstanceOf($ast, Expr\ArrayDimFetch::class) as $fetch) {
		$dim = $fetch->dim;
		$assigned = ($dim instanceof Expr\Variable ? upgradeAssigned($dim, $ast) : []);
		if (!$dim instanceof Expr\Array_ && !($assigned && !array_filter($assigned, fn (Expr $expr): bool => !$expr instanceof Expr\Array_))) {
			continue;
		}
		$parent = $fetch->getAttribute('parent');
		$read = !($parent instanceof Expr\Isset_ || $parent instanceof Expr\Empty_ || $parent instanceof Stmt\Unset_
			|| (($parent instanceof Expr\Assign || $parent instanceof Expr\AssignRef || $parent instanceof Expr\AssignOp) && $parent->var === $fetch));
		$var = $fetch->var;
		if ($read && ($var instanceof Expr\PropertyFetch || $var instanceof Expr\NullsafePropertyFetch) && !($var->var instanceof Expr\Variable && $var->var->name === 'this')) {
			// $db->table is a new result each time
			$edits[] = [$var->getEndFilePos() + 1, $dim->getStartFilePos(), "->where("]; // the array itself can be upgraded too
			$edits[] = [$dim->getEndFilePos() + 1, $fetch->getEndFilePos() + 1, ")->fetch()"];
		} else {
			$messages[] = [$fetch->getStartLine(), "warning: an array offset of Result throws, use where()->fetch() on a new result"];
		}
	}

	// signatures of methods and properties in the descendants of NotORM classes
	foreach ($finder->findInstanceOf($ast, Stmt\Class_::class) as $class) {
		$ancestors = upgradeAncestors(upgradeParents($class), $classes);
		if (!$ancestors) {
			continue;
		}
		$className = ($class->namespacedName ?? 'class@anonymous');
		foreach ($class->getMethods() as $method) {
			$name = UPGRADE_METHODS[$method->name->toLowerString()] ?? $method->name->toString();
			if ($name !== $method->name->toString()) {
				$replace($method->name, $name);
			}
			$parent = upgradeMember($ancestors, fn (ReflectionClass $ancestor): ?ReflectionMethod => ($ancestor->hasMethod($name) ? $ancestor->getMethod($name) : null));
			if ($parent) {
				if (!$method->returnType && $parent->hasReturnType()) {
					$insert(upgradeParamsEnd($tokens, $method), ": " . upgradeType($parent->getReturnType(), $parent->getDeclaringClass()));
				}
				$required = count(array_filter($method->params, fn (Node\Param $param): bool => !$param->default && !$param->variadic));
				if (count($method->params) < $parent->getNumberOfParameters() || $required > $parent->getNumberOfRequiredParameters()) {
					$messages[] = [
						$method->getStartLine(),
						"warning: $className::$name() must accept the parameters of " . $parent->getDeclaringClass()->getName() . "::$name(), add them or their default values",
					];
				}
			}
		}
		foreach ($class->getProperties() as $property) {
			if (!$property->type) {
				foreach ($property->props as $prop) {
					$name = $prop->name->toString();
					$parent = upgradeMember($ancestors, fn (ReflectionClass $ancestor): ?ReflectionProperty => ($ancestor->hasProperty($name) ? $ancestor->getProperty($name) : null));
					if ($parent && $parent->hasType()) {
						if (count($property->props) > 1) {
							$messages[] = [$property->getStartLine(), "warning: declare $className::\$$name separately with type " . upgradeType($parent->getType(), $parent->getDeclaringClass())];
						} else {
							$insert($prop->getStartFilePos(), upgradeType($parent->getType(), $parent->getDeclaringClass()) . " ");
							if (!$prop->default && $parent->getType()->allowsNull()) {
								$insert($prop->name->getEndFilePos() + 1, " = null"); // untyped properties are null by default
							}
						}
					}
				}
			}
		}
		if (array_filter($ancestors, fn (ReflectionClass $ancestor): bool => $ancestor->getName() === NotORM\Row::class || $ancestor->isSubclassOf(NotORM\Row::class))) {
			// Row::$primary was renamed to $id because Base::$primary is the column name
			$fetches = $finder->find($class->stmts, fn (Node $node): bool => ($node instanceof Expr\PropertyFetch || $node instanceof Expr\NullsafePropertyFetch)
				&& $node->var instanceof Expr\Variable && $node->var->name === 'this'
				&& $node->name instanceof Node\Identifier && $node->name->toString() === 'primary');
			foreach ($fetches as $fetch) {
				$replace($fetch->name, 'id');
			}
		}
	}

	// NotORM.php doesn't include the other files
	foreach ($finder->findInstanceOf($ast, Expr\Include_::class) as $include) {
		$strings = $finder->find($include->expr, fn (Node $node): bool => $node instanceof Scalar\String_ || $node instanceof Node\InterpolatedStringPart);
		foreach ($strings as $string) {
			if (preg_match('~(^|[/\\\\])NotORM\.php$~i', $string->value)) {
				$messages[] = [$include->getStartLine(), "warning: load NotORM by Composer's autoload: require 'vendor/autoload.php'"];
			}
		}
	}

	usort($edits, fn (array $a, array $b): int => $b[0] <=> $a[0]);
	$end = PHP_INT_MAX;
	foreach ($edits as [$start, $stop, $replacement]) {
		if ($stop <= $end) { // the overlapping edits are skipped
			$code = substr_replace($code, $replacement, $start, $stop - $start);
			$end = $start;
		}
	}
	usort($messages, fn (array $a, array $b): int => $a[0] <=> $b[0]);
	return [$code, $messages];
}

/** Find a non-private member in the ancestors
* @template T of ReflectionMethod|ReflectionProperty
* @param list<ReflectionClass<object>> $ancestors
* @param callable(ReflectionClass<object>): ?T $get
* @return ?T
*/
function upgradeMember(array $ancestors, callable $get): ReflectionMethod|ReflectionProperty|null {
	foreach ($ancestors as $ancestor) {
		$member = $get($ancestor);
		if ($member && !$member->isPrivate()) {
			return $member;
		}
	}
	return null;
}

/** Get position after the closing parenthesis of the parameters
* @param Token[] $tokens
*/
function upgradeParamsEnd(array $tokens, Stmt\ClassMethod $method): int {
	$depth = 0;
	for ($i = $method->name->getEndTokenPos() + 1; isset($tokens[$i]); $i++) {
		if ($tokens[$i]->text === "(") {
			$depth++;
		} elseif ($tokens[$i]->text === ")" && !--$depth) {
			return $tokens[$i]->getEndPos();
		}
	}
	throw new LogicException("Parameters of $method->name not closed");
}

/** Format a type usable in any namespace */
function upgradeType(ReflectionType $type, ReflectionClass $class): string {
	if ($type instanceof ReflectionUnionType) {
		return implode("|", array_map(fn (ReflectionType $type): string => upgradeType($type, $class), $type->getTypes()));
	}
	$name = $type->getName();
	$return = match (true) {
		$name === 'self' => "\\" . $class->getName(),
		$name === 'static' || $type->isBuiltin() => $name,
		default => "\\$name",
	};
	return ($type->allowsNull() && $name !== 'mixed' && $name !== 'null' ? "?$return" : $return);
}

/** Find out whose fetch() is compared
* @param Node[] $ast
* @return ?string 'notorm', 'pdo', 'unsure' or null if it is not fetch()
*/
function upgradeFetch(Expr $expr, array $ast): ?string {
	while ($expr instanceof Expr\Assign) { // ($row = $result->fetch()) !== false
		$expr = $expr->expr;
	}
	if (!$expr instanceof Expr\Variable) {
		return upgradeFetchCall($expr, $ast);
	}
	$kinds = array_unique(array_map(fn (Expr $assigned): ?string => upgradeFetchCall($assigned, $ast), upgradeAssigned($expr, $ast)));
	return match (true) {
		!array_filter($kinds) => null,
		count($kinds) === 1 => reset($kinds),
		default => 'unsure',
	};
}

/** Find out whose fetch() is called
* @param Node[] $ast
* @return ?string 'notorm', 'pdo', 'unsure' or null if it is not fetch()
*/
function upgradeFetchCall(Expr $call, array $ast): ?string {
	if (!($call instanceof Expr\MethodCall || $call instanceof Expr\NullsafeMethodCall) || !$call->name instanceof Node\Identifier || $call->name->toLowerString() !== 'fetch') {
		return null;
	}
	$arg = $call->args[0] ?? null;
	$arg = ($arg instanceof Node\Arg ? $arg->value : null);
	if ($arg instanceof Scalar\String_ || $arg instanceof Scalar\InterpolatedString) {
		return 'notorm'; // column name
	}
	if ($arg instanceof Scalar\Int_ || ($arg instanceof Expr\ClassConstFetch && $arg->class instanceof Name && strtolower(upgradeName($arg->class)) === 'pdo')) {
		return 'pdo'; // fetch mode
	}
	return upgradeReceiver($call->var, $ast);
}

/** Find out whether an object comes from NotORM or PDO
* @param Node[] $ast
* @return string 'notorm', 'pdo' or 'unsure'
*/
function upgradeReceiver(Expr $expr, array $ast, int $depth = 0): string {
	if ($expr instanceof Expr\MethodCall || $expr instanceof Expr\NullsafeMethodCall) {
		$method = ($expr->name instanceof Node\Identifier ? $expr->name->toLowerString() : '');
		return match (true) {
			in_array($method, UPGRADE_RESULT_METHODS, true) => 'notorm',
			in_array($method, UPGRADE_PDO_METHODS, true) => 'pdo',
			default => upgradeReceiver($expr->var, $ast, $depth),
		};
	}
	if ($expr instanceof Expr\PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch || $expr instanceof Expr\ArrayDimFetch) {
		return upgradeReceiver($expr->var, $ast, $depth); // $db->table, $db->table[1]
	}
	if ($expr instanceof Expr\New_ && $expr->class instanceof Name) {
		$class = strtolower(upgradeName($expr->class));
		return match (true) {
			str_starts_with($class, "notorm\\") => 'notorm',
			$class === 'pdo' => 'pdo',
			default => 'unsure',
		};
	}
	if ($expr instanceof Expr\Variable && $depth < 5) {
		$kinds = array_unique(array_map(fn (Expr $assigned): string => upgradeReceiver($assigned, $ast, $depth + 1), upgradeAssigned($expr, $ast)));
		return (count($kinds) === 1 ? reset($kinds) : 'unsure');
	}
	return 'unsure';
}

/** Get values assigned to a variable in its function
* @param Node[] $ast
* @return list<Expr>
*/
function upgradeAssigned(Expr\Variable $variable, array $ast): array {
	if (!is_string($variable->name) || $variable->name === 'this') {
		return [];
	}
	$scope = $variable;
	do {
		$scope = $scope->getAttribute('parent');
	} while ($scope && !$scope instanceof Node\FunctionLike);
	$assigns = (new NodeFinder)->find(
		($scope ? (array) $scope->getStmts() : $ast),
		fn (Node $node): bool => $node instanceof Expr\Assign && $node->var instanceof Expr\Variable && $node->var->name === $variable->name,
	);
	return array_map(fn (Expr\Assign $assign): Expr => $assign->expr, $assigns);
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
	$args = array_slice($argv, 1);
	$paths = array_values(array_diff($args, ['--dry-run']));
	if (!$paths) {
		fwrite(STDERR, "Upgrades code using NotORM 1 to NotORM 2, see README.md\nUsage: php upgrade.php [--dry-run] path...\n");
		exit(1);
	}
	try {
		$messages = upgrade($paths, !in_array('--dry-run', $args, true));
	} catch (RuntimeException $e) {
		fwrite(STDERR, $e->getMessage() . "\n");
		exit(1);
	}
	echo implode("", array_map(fn (string $message): string => "$message\n", $messages));
	exit(preg_grep('~^[^ ]+: error: ~', $messages) ? 1 : 0);
}
