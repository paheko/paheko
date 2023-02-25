<?php

use Garradin\Web\Web;
use Garradin\Files\Files;

// Refresh pages
Web::sync(true);

$pages = $db->iterate('SELECT * FROM web_pages;');
Files::disableQuota();

foreach ($pages as $data) {
	$page = new \Garradin\Entities\Web\Page;
	$page->exists(true);
	$page->load((array) $data);

	// Add type and modified date to each TXT file
	$page->syncFile();

	// Sync search
	$page->syncSearch();
}
