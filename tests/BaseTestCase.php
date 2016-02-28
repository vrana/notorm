<?php
namespace NotORM\Tests;

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
	protected static $params, $db_type;

	/**
	 * @var PDO
	 */
	protected static $pdo;
	protected $db = null;
	protected $time;


	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		$params = static::getParam('databases');

		if (static::$db_type === 'sqlite') {
			self::$pdo = new \PDO($params[static::$db_type]['dsn']);
		} else {
			self::$pdo= new \PDO(
				$params[static::$db_type]['dsn'],
				$params[static::$db_type]['username'],
				$params[static::$db_type]['password']
			);
		}

		self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
		self::$pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
		self::$pdo->exec(file_get_contents($params[static::$db_type]['fixture']));

	}

	public static function tearDownBeforeClass()
	{
		parent::tearDownBeforeClass();
		self::$pdo = null;
	}

	/**
	 * Setup the test environment.
	 */
	public function setUp()
	{
		$this->db = is_null($this->db) ? new \NotORM\Instance(self::$pdo) : $this->db;
		$this->time = microtime(true);
	}

	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		$this->db = null;
	}

	/**
	 * Returns a test configuration param from config.php
	 * @param  string $name params name
	 * @param  mixed $default default value to use when param is not set.
	 * @return mixed  the value of the configuration param
	 */
	public static function getParam($name, $default = null)
	{
		if (static::$params === null) {
			static::$params = require(__DIR__ . '/config.php');
		}
		return isset(static::$params[$name]) ? static::$params[$name] : $default;
	}

	/**
	 * Test instance
	 *
	 * @test
	 */
	public function testInstanceOf()
	{
		$this->assertInstanceOf('PDO', self::$pdo);
		$this->assertInstanceOf('\NotORM\Instance', $this->db);
	}

	/**
	 * Basic operations
	 */
	public function testBasic()
	{
		$expected = [
			'Adminer' => [
				'authors' => ['Jakub Vrana'],
				'tags' => ['PHP', 'MySQL'],
			],
			'JUSH' => [
				'authors' => ['Jakub Vrana'],
				'tags' => ['JavaScript'],
			],
			'Nette' => [
				'authors' => ['David Grudl'],
				'tags' => ['PHP'],
			],
			'Dibi' => [
				'authors' => ['David Grudl'],
				'tags' => ['PHP', 'MySQL'],
			]
		];

		foreach ($this->db->application() as $application) {
			$result[$application['title']]['authors'][] = $application->author['name'];
			foreach ($application->application_tag() as $application_tag) {
				$result[$application['title']]['tags'][] = $application_tag->tag['name'];
			}
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Single row detail
	 */
	public function testDetail()
	{
		$expected = [
			'id' => '1',
			'author_id' => '11',
			'maintainer_id' => '11',
			'title' => 'Adminer',
			'web' => 'http://www.adminer.org/',
			'slogan' => 'Database management in single PHP file',
		];

		$application = $this->db->application[1];
		foreach ($application as $key => $val) {
			$result[$key] = $val;
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Search and order items
	 */
	public function testSearchOrder()
	{
		$expected = [
			0 => 'Adminer',
			1 => 'Dibi',
			2 => 'JUSH',
		];

		foreach ($this->db->application('web LIKE ?', 'http://%')->order('title')->limit(3) as $application) {
			$result[] = $application['title'];
		}


		$this->assertEquals($expected, $result);
	}

	/**
	 * Find one item by title
	 */
	public function testFindOne()
	{
		$expected = [
			0 => 'PHP',
			1 => 'Database management in single PHP file',
		];

		$application = $this->db->application('title', 'Adminer')->fetch();
		foreach ($application->application_tag('tag_id', 21) as $application_tag) {
			$result[] = $application_tag->tag['name'];
		}
		$result[] = $this->db->application('title', 'Adminer')->fetch('slogan');

		$this->assertEquals($expected, $result);
	}

	/**
	 * Calling __toString()
	 */
	public function testToString()
	{
		$expected = [ 1, 2, 3, 4 ];

		foreach ($this->db->application() as $application) {
			$result[] = (string)$application;
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Aggregation functions
	 */
	public function testAggregation()
	{
		$expected = [
			'applications' => 4,
			'Adminer' => 2,
			'JUSH' => 1,
			'Nette' => 1,
			'Dibi' => 2,
		];

		$count = $this->db->application()->count("*");
		$result['applications'] = $count;
		foreach ($this->db->application() as $application) {
			$count = $application->application_tag()->count("*");
			$result[$application['title']] = $count;
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Subqueries
	 */
	public function testSubQuery()
	{
		$expected = [
			'Adminer',
			'JUSH',
			'Nette',
			'Dibi',
		];

		$unknownBorn = $this->db->author("born", null); // authors with unknown date of born
		foreach ($this->db->application("author_id", $unknownBorn) as $application) { // their applications
			$result[] = $application['title'];
		}

		$this->assertEquals($expected, $result);
	}


	/**
	 * Session cache
	 */
	public function testSessionCache()
	{
		$expected = [
			'SELECT * FROM application',
			'SELECT id, title, author_id FROM application',
			'SELECT * FROM application',
			'SELECT id, title, author_id, slogan FROM application',
		];

		$_SESSION = array(); // not session_start() - headers already sent
		$cache = new \NotORM\Instance(self::$pdo, null, new \NotORM\CacheSession());
		$applications = $cache->application();
		$application = $applications->fetch();
		$application['title'];
		$application->author['name'];
		$result[] = (string)$applications; // get all columns with no cache
		$applications->__destruct();
		$applications = $cache->application();
		$application = $applications->fetch();
		$result[] = (string)$applications; // get only title and author_id
		$application["slogan"]; // script changed and now we want also slogan
		$result[] = (string)$applications; // all columns must have been retrieved to get slogan
		$applications->__destruct();
		$applications = $cache->application();
		$applications->fetch();
		$result[] = (string)$applications; // next time, get only title, author_id and slogan

		$this->assertEquals($expected, $result);
	}

	/**
	 * Insert, update, delete
	 */
	public function testUpdate()
	{
		$expected = [
			'Texy',
			'1 row updated.',
			'http://texy.info/',
			'1 row deleted.',
			'0 rows found.',
		];

		$id = 5; // auto_increment is disabled in demo
		$application = $this->db->application()->insert([
			'id' => $id,
			'author_id' => $this->db->author[12],
			'title' => new \NotORM\Literal("'Texy'"),
			'web' => '',
			'slogan' => 'The best humane Web text generator',
		]);
		$application_tag = $application->application_tag()->insert(['tag_id' => 21]);
		// retrieve the really stored value
		$application = $this->db->application[$id];
		$result[] = $application['title'];
		$application['web'] = 'http://texy.info/';
		$result[] = $application->update() . ' row updated.';
		$result[] = $this->db->application[$id]['web'];
		$this->db->application_tag('application_id', 5)->delete(); // foreign keys may be disabled
		$result[] = $application->delete() . ' row deleted.';
		$result[] = count($this->db->application('id', $id)) . ' rows found.';

		$this->assertEquals($expected, $result);
	}

	/**
	 * fetchPairs()
	 */
	public function testPairs()
	{
		$expected = [
			[
				1 => 'Adminer',
				4 => 'Dibi',
				2 => 'JUSH',
				3 => 'Nette',
			],
			[1 => 1, 2 => 2, 3 => 3, 4 => 4]
		];

		$result[] = $this->db->application()->order('title')->fetchPairs('id', 'title');
		$result[] = $this->db->application()->order('id')->fetchPairs('id', 'id');

		$this->assertEquals($expected, $result);
		//TODO 'zend_mm_heap corrupted' check the problem in other env
		//TempFix zend.enable_gc = 0
	}

	/**
	 * via()
	 */
	public function testVia()
	{
		$expected = [
			'Jakub Vrana' => 'Adminer',
			'David Grudl' => 'Dibi',
		];

		foreach ($this->db->author() as $author) {
			$applications = $author->application()->via('maintainer_id');
			foreach ($applications as $application) {
				$result[$author['name']] = $application['title'];
			}
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * ORDER from other table
	 */
	public function testJoin()
	{
		$expected = [
			'Jakub Vrana' => 'JUSH',
			'David Grudl' => 'Nette',
			0 => 'PHP',
			1 => 'MySQL',
			2 => 'JavaScript',
		];


		foreach ($this->db->application()->order('author.name, title') as $application) {
			$result[$application->author['name']] = $application['title'];
		}
		foreach ($this->db->application_tag('application.author.name', 'Jakub Vrana')->group('application_tag.tag_id') as $application_tag) {
			$result[] = $application_tag->tag['name'];
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * WHERE
	 */
	public function testWhere()
	{
		$expected = [
			'4',
			'1, 2, 3',
			'1, 2, 3',
			'1, 2',
			'',
			'1, 2, 3, 4',
			'1, 3',
			'3',
		];

		foreach ([
			         $this->db->application('id', 4),
			         $this->db->application('id < ?', 4),
			         $this->db->application('id < ?', [4]),
			         $this->db->application('id', [1, 2]),
			         $this->db->application('id', null),
			         $this->db->application('id', $this->db->application()),
			         $this->db->application('id < ?', 4)->where('maintainer_id IS NOT NULL'),
			         $this->db->application(['id < ?' => 4, 'author_id' => 12]),
		         ] as $row) {
			$result[] = implode(', ', array_keys(iterator_to_array($row->order('id')))); // aggregation("GROUP_CONCAT(id)") is not available in all drivers
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Multiple arguments
	 */
	public function testMultiple()
	{
		$expected = [
			[1 => 22],
			[1 => 21],
		];

		$application = $this->db->application[1];
		foreach ($application->application_tag()
			         ->select('application_id', 'tag_id')
			         ->order('application_id DESC', 'tag_id DESC')
		         as $application_tag) {
			$result[][$application_tag['application_id']] = $application_tag['tag_id'];
		}

		$this->assertEquals($expected, $result);
	}



	/**
	 * Transactions
	 */
	public function testTransaction()
	{
		$this->db->transaction = 'BEGIN';
		$this->db->tag()->insert(['id' => 99, 'name' => 'Test']);
		$this->db->transaction = 'ROLLBACK';
		$result = $this->db->tag[99];
		$this->assertNull($result);
	}

	/**
	 * Array offset
	 */
	public function testArrayOffset()
	{
		$expected = [2, 2];

		$where = [
			'author_id' => '11',
			'maintainer_id' => null,
		];

		$result[] = $this->db->application[$where]['id'];
		$applications = $this->db->application()->order('id');
		$result[] = $applications[$where]['id'];

		$this->assertEquals($expected, $result);
	}


	/**
	 * Simple UNION
	 */
	public function testSimpleUNION()
	{
		$expected = [23, 22, 21, 4, 3, 2, 1];

		$applications = $this->db->application()->select('id');
		$tags = $this->db->tag()->select('id');
		foreach ($applications->union($tags)->order('id DESC') as $row) {
			$result[] = $row['id'];
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Simple UNION
	 */
	public function testInsertUpdate()
	{
		$expected = [1, 2, 1];

		for ($i = 0; $i < 2; $i++) {
			$result[] = $this->db->application()->insert_update(['id' => 5], ['author_id' => 12, 'title' => 'Texy', 'web' => '', 'slogan' => $i]);
		}
		$application = $this->db->application[5];
		$result[] = $application->application_tag()->insert_update(['tag_id' => 21], []);
		$this->db->application('id', 5)->delete();

		$this->assertEquals($expected, $result);
	}

	/**
	 * Table prefix
	 */
	public function testTablePrefix()
	{
		$expected = 'SELECT prefix_application.* FROM prefix_application LEFT JOIN prefix_author AS author ON prefix_application.author_id = author.id WHERE (author.name = \'Jakub Vrana\')';

		$prefix = new \NotORM\Instance(self::$pdo, new \NotORM\StructureConvention('id', '%s_id', '%s', 'prefix_'));
		$applications = $prefix->application('author.name', 'Jakub Vrana');
		$result = (string)$applications;

		$this->assertEquals($expected, $result);
	}

	/**
	 * Select locking
	 */
	public function testLock()
	{
		$expected = 'SELECT * FROM application FOR UPDATE';

		$result = (string) $this->db->application()->lock();

		$this->assertEquals($expected, $result);
	}

	/**
	 * Backwards join
	 */
	public function testBackJoin()
	{
		$expected = [
			'Jakub Vrana' => 3,
			'David Grudl' => 2,
		];

		foreach ($this->db->author()->select("author.*, COUNT(DISTINCT application:application_tag:tag_id) AS tags")->group("author.id")->order("tags DESC") as $autor) {
			$result[$autor['name']] = $autor['tags'];
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * IN operator
	 */
	public function testIn()
	{
		$expected = [0, 1, 2, 3];


		$result[] = $this->db->application('maintainer_id', [])->count('*');
		$result[] = $this->db->application('maintainer_id', [11])->count('*');
		$result[] = $this->db->application('NOT maintainer_id', [11])->count('*');
		$result[] = $this->db->application('NOT maintainer_id', [])->count('*');

		$this->assertEquals($expected, $result);
	}

	/**
	 * IN operator with MultiResult
	 */
	public function testInMulti()
	{
		$expected = [
			[ 11, 1, 21 ],
			[ 11, 1, 22 ],
			[ 11, 2, 23 ],
			[ 12, 3, 21 ],
			[ 12, 4, 21 ],
			[ 12, 4, 22 ],
		];


		foreach ($this->db->author()->order('id') as $author) {
			foreach ($this->db->application_tag('application_id', $author->application())->order('application_id, tag_id') as $application_tag) {
				$result[] = [ (string) $author, $application_tag['application_id'], $application_tag['tag_id']];
			}
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Literal value with parameters
	 */
	public function testLiteral()
	{
		$expected = [ 3 ];


		foreach ($this->db->author()->select(new \NotORM\Literal('? + ?', 1, 2))->fetch() as $val) {
			$result[] = $val;
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Update row through property
	 */
	public function testRowSet()
	{
		$expected = 1;

		$application = $this->db->application[1];
		$application->author = $this->db->author[12];
		$result = $application->update();

		$this->assertEquals($expected, $result);

		$application->update(['author_id' => 11]);
	}

	/**
	 * Custom row class
	 */
	public function testRowClass()
	{
		$expected = [ 'Adminer', 'Jakub Vrana' ];

		$this->db->rowClass = '\NotORM\Tests\TestRow';
		$application = $this->db->application[1];
		$result[] = $application['test_title'];
		$result[] = $application->author['test_name'];
		$this->db->rowClass = 'Row';

		$this->assertEquals($expected, $result);
	}

	/**
	 * DateTime processing
	 */
	public function testDateTime()
	{
		$expected = '2011-08-30 00:00:00';

		$date = new \DateTime('2011-08-30');

		$this->db->application()->insert([
			'id' => 5,
			'author_id' => 11,
			'title' => $date,
			'slogan' => new \NotORM\Literal('?', $date),
		]);
		$application = $this->db->application()->where('title = ?', $date)->fetch();
		$result = (string) $application['slogan'];
		$application->delete();

		$this->assertEquals($expected, $result);
	}

	/**
	 * IN with NULL value
	 */
	public function testInNull()
	{
		$expected = [ 1, 2 ];

		foreach ($this->db->application('maintainer_id', [11, null]) as $application) {
			$result[] = $application['id'];
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Structure for non-conventional column
	 */
	public function testStructure()
	{
		$expected = [ 'Jakub Vrana', 'Adminer' ];

		$convention = new \NotORM\Instance(self::$pdo, new \NotORM\Tests\SoftwareConvention);
		$maintainer = $convention->application[1]->maintainer;
		$result[] = $maintainer['name'];
		foreach ($maintainer->application()->via('maintainer_id') as $application) {
			$result[] = $application['title'];
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Update primary key of a row
	 */
	public function testUpdatePrimary()
	{
		$expected = [ 24, 25, 1, 1 ];

		$application = $this->db->tag()->insert(['id' => 24, 'name' => 'HTML']);
		$result[] = (string) $application['id'];
		$application['id'] = 25;
		$result[] = (string) $application['id'];
		$result[] = (string) $application->update();
		$result[] = (string) $application->delete();

		$this->assertEquals($expected, $result);
	}

	/**
	 * Using the same MultiResult several times
	 */
	public function testMultiResultLoop()
	{
		$expected = [ 2, 2, 2, 2 ];

		$application = $this->db->application[1];
		for ($i = 0; $i < 4; $i++) {
			$result[] = (string) count($application->application_tag());
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Calling and()
	 */
	public function testAnd()
	{
		$expected = 'Adminer';

		foreach ($this->db->application('author_id', 11)->and('maintainer_id', 11) as $application) {
			$result = $application['title'];
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Calling or()
	 */
	public function testOr()
	{
		$expected = [
			'Adminer',
			'JUSH',
		];

		foreach ($this->db->application('author_id', 11)->or('maintainer_id', 11)->order('title') as $application) {
			$result[] = $application['title'];
		}

		$this->assertEquals($expected, $result);
	}


	/**
	 * Calling or()
	 */
	public function testParens()
	{
		$expected = [
			'Adminer',
			'Dibi',
			'Nette',
		];

		$applications = $this->db->application()
			->where('(author_id', 11)->and('maintainer_id', 11)->where(')')
			->or('(author_id', 12)->and('maintainer_id', 12)->where(')');

		foreach ($applications->order('title') as $application) {
			$result[] = $application['title'];
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Test debug
	 */
	public function testDebug()
	{
		$expected = [ 4, 1, 10 ];

		$applications = $this->db->application;

		$time_start = microtime(true);
		$this->db->debug = function () use ($time_start) {
			echo 'Start: ' . $time_start . PHP_EOL;
		};

		$result[] = $applications->max('id');

		$this->db->debugTimer = function () use ($time_start) {
			$time_end = microtime(true);
			$time = $time_end - $time_start;
			echo 'End: ' . $time_end . PHP_EOL . 'Total: ' . $time . PHP_EOL;
		};
		$this->db->debug = false;

		$result[] = $applications->min('id');
		$result[] = $applications->sum('id');


		$this->assertEquals($expected, $result);
	}

	public function testJsonSerialize()
	{
		$expected = <<<'JSON'
{
    "1": {
        "id": "1",
        "author_id": "11",
        "maintainer_id": "11",
        "title": "Adminer",
        "web": "http:\/\/www.adminer.org\/",
        "slogan": "Database management in single PHP file"
    },
    "2": {
        "id": "2",
        "author_id": "11",
        "maintainer_id": null,
        "title": "JUSH",
        "web": "http:\/\/jush.sourceforge.net\/",
        "slogan": "JavaScript Syntax Highlighter"
    },
    "3": {
        "id": "3",
        "author_id": "12",
        "maintainer_id": "12",
        "title": "Nette",
        "web": "http:\/\/nettephp.com\/",
        "slogan": "Nette Framework for PHP 5"
    },
    "4": {
        "id": "4",
        "author_id": "12",
        "maintainer_id": "12",
        "title": "Dibi",
        "web": "http:\/\/dibiphp.com\/",
        "slogan": "Database Abstraction Library for PHP 5"
    }
}
JSON;


		$result = json_encode(array_map('iterator_to_array', iterator_to_array($this->db->application())), JSON_PRETTY_PRINT);

		$this->assertEquals($expected, $result);

	}

}
