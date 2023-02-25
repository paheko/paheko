<?php

use Garradin\Web\Web;

// Refresh pages
Web::sync(true);

$pages = $db->iterate('SELECT * FROM web_pages;');

foreach ($pages as $data) {
	$page = new \Garradin\Entities\Web\Page;
	$page->exists(true);
	$page->load((array) $data);

	// Add type and modified date to each TXT file
	$page->syncFile();

	// Sync search
	$page->syncSearch();
}