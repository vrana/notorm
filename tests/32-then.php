<?php
include_once dirname(__FILE__) . "/connect.inc.php";

NotORM::then(function () use ($software) {
	$software->author()->order("id")->then(function ($authors) {
		if (count($authors)) {
			echo "Authors:\n";
			foreach ($authors as $author) {
				echo "$author[name]\n";
			}
			echo "\n";
		}
	});
	
	echo "Application tags:\n";
	$software->application_tag()->order("application_id, tag_id")->thenForeach(function ($application_tag) {
		NotORM::then($application_tag->application, $application_tag->tag, function ($application, $tag) {
			echo "$application[title]: $tag[name]\n";
		});
	});
});
