--TEST--
Translation in second table
--FILE--
<?php

include_once dirname(__FILE__) . "/connect.inc.php";

class NotORM_Row_Translation extends NotORM_Row {
	static $lang = "cs";

	function offsetExists($offset) {
		if (!isset($this->row[$offset])) {
			$table = $this->result->table . "_translation";
			foreach ($this->$table("language", array(self::$lang, "en"))->order("language = 'en'")->limit(1) as $row) {
				foreach ($row as $key => $val) {
					$this->row[$key] = $val;
				}
			}
		}
		return parent::offsetExists($offset);
	}

	function offsetGet($offset) {
		$this->offsetExists($offset);
		return parent::offsetGet($offset);
	}
}

$software->rowClass = 'NotORM_Row_Translation';

foreach ($software->article() as $article) {
	echo "$article[id] - $article[title]\n";
}
?>
--EXPECTF--
1 - O Admineru
2 - About JUSH
3 - O Nette
