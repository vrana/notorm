<?php
namespace NotORM\Tests;

/**
 * Class MysqlTest
 * @package Tests
 * @group mysql
 */
class MysqlTest extends BaseTestCase {
	protected static $db_type = 'mysql';

	/**
	 * Limit and offset
	 */
	public function testOffset()
	{
		$expected = [
			'MySQL',
			'MySQL',
			'MySQL',
		];

		$application = $this->db->application[1];
		foreach ($application->application_tag()->order('tag_id')->limit(3, 1) as $application_tag) {
			$result[] = $application_tag->tag['name'];
		}
		foreach ($this->db->application() as $application) {
			foreach ($application->application_tag()->order('tag_id')->limit(1, 1) as $application_tag) {
				$result[] = $application_tag->tag['name'];
			}
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Complex UNION
	 */
	public function testUnion()
	{
		$expected = [ 22, 21, 4 ];

		$applications = $this->db->application()->select('id')->order('id DESC')->limit(2);
		$tags = $this->db->tag()->select('id')->order('id')->limit(2);
		foreach ($applications->union($tags)->order('id DESC')->limit(3) as $row) {
			$result[] = $row['id'];
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * Extended insert
	 */
	public function testExtendedInsert()
	{
		$expected = [[3 => 23], [3 => 22], [3 => 21]];

		$application = $this->db->application[3];
		$application->application_tag()->insert(['tag_id' => 22], ['tag_id' => 23]);
		foreach ($application->application_tag()->order('tag_id DESC') as $application_tag) {
			$result[] = [$application_tag['application_id'] => $application_tag['tag_id']];
		}
		$application->application_tag('tag_id', [22, 23])->delete();

		$this->assertEquals($expected, $result);
	}

	/**
	 * Discovery
	 */
	public function testDiscovery()
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

		$discovery = new \NotORM\Instance(
			self::$pdo,
			new \NotORM\StructureDiscovery(
				self::$pdo,
				new \NotORM\Cache()
			),
			new \NotORM\Cache()
		);

		foreach ($discovery->application() as $application) {
			$result[$application['title']]['authors'][] = $application->author_id['name'];
			foreach ($application->application_tag() as $application_tag) {
				$result[$application['title']]['tags'][] = $application_tag->tag_id['name'];
			}
		}

		$this->assertEquals($expected, $result);
	}
}
