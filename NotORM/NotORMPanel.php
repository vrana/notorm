<?php


class NotORMPanel extends Nette\Object implements Nette\IDebugPanel
{
	/** @var int maximum SQL length */
	static public $maxLength = 1000;

	/** @var int logged time */
	private $totalTime = 0;

	/** @var array */
	private $queries = array();



	public function logQuery($sql, array $params, $time)
	{
		$this->totalTime += $time;
		$this->queries[] = array($sql, $params, $time);
	}



	public function getId()
	{
		return 'NotORM';
	}



	public function getTab()
	{
		return '<span title="NotORM"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAHpJREFUOMvVU8ENgDAIBON8dgY7yU3SHTohfoQUi7FGH3pJEwI9oBwl+j1YDRGR8AIzA+hiAIxLsoOW1R3zB9Cks1VKmaQWXz3wHWEJpBbilF3wivxKB9OdiUfDnJ6Q3RNGyWp3MraytbKqjADkrIvhPYgSDG3itz/TBsqre3ItA1W8AAAAAElFTkSuQmCC">' . count($this->queries) . ' queries</span>';
	}



	public function getPanel()
	{
		if (!$this->queries) return;

		$content = "
<h1>Queries: " . count($this->queries) . ($this->totalTime === NULL ? '' : ', time: ' . sprintf('%0.3f', $this->totalTime * 1000) . ' ms') . "</h1>

<style>
	#nette-debug-notorm td.notorm-sql { background: white }
	#nette-debug-notorm .nette-alt td.notorm-sql { background: #F5F5F5 }
	#nette-debug-notorm .notorm-sql div { display: none; margin-top: 10px; max-height: 150px; overflow:auto }
</style>

<div class='nette-inner'>
<table>
<tr>
	<th>Time</th><th>SQL Statement</th><th>Params</th>
</tr>
";
		$i = 0;
		foreach ($this->queries as $query) {
			list($sql, $params, $time) = $query;
			$params = htmlSpecialChars(implode(', ', $params));
			$content .= "
<tr>
	<td>" . sprintf('%0.3f', $time * 1000) . "</td>
	<td class='notorm-sql'>" . self::dump(strlen($sql) > self::$maxLength ? substr($sql, 0, self::$maxLength) . '...' : $sql, TRUE) . "</td>
	<td>{$params}</td>
</tr>
";
		}
		$content .= '</table></div>';
		return $content;
	}



	public static function dump($sql)
	{
		static $keywords1 = 'SELECT|UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';
		static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|TRUE|FALSE';

		// insert new lines
		$sql = " $sql ";
		$sql = preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

		// reduce spaces
		$sql = preg_replace('#[ \t]{2,}#', " ", $sql);

		$sql = wordwrap($sql, 100);
		$sql = preg_replace("#([ \t]*\r?\n){2,}#", "\n", $sql);

		// syntax highlight
		$sql = htmlSpecialChars($sql);
		$sql = preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is", function($matches) {
			if (!empty($matches[1])) // comment
				return '<em style="color:gray">' . $matches[1] . '</em>';

			if (!empty($matches[2])) // error
				return '<strong style="color:red">' . $matches[2] . '</strong>';

			if (!empty($matches[3])) // most important keywords
				return '<strong style="color:blue">' . $matches[3] . '</strong>';

			if (!empty($matches[4])) // other keywords
				return '<strong style="color:green">' . $matches[4] . '</strong>';
		}, $sql);
		return '<pre class="dump">' . trim($sql) . "</pre>\n";
	}

}
