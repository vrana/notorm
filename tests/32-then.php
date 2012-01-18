<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$software->author()->order("id")->then(function ($authors) {
	foreach ($authors as $author) {
		echo "$author[name]\n";
	}
});
echo "\n";

$software->application_tag()->order("application_id, tag_id")->then(function ($application_tags) {
	foreach ($application_tags as $application_tag) {
		NotORM::then($application_tag->application, $application_tag->tag, function ($application, $tag) {
			echo "$application[title]: $tag[name]\n";
		});
	}
});
