<?php

namespace Garradin;

use Garradin\Web\Render\Markdown;

const INSTALL_PROCESS = true;

require __DIR__ . '/../src/include/init.php';

foreach (glob(__DIR__ . '/../doc/admin/*.md') as $file) {
	$r = new Markdown;
	$t = $r->render(file_get_contents($file));

	$title = $r->toc[0]['label'] ?? $file;

	$out = '<!DOCTYPE html>
	<html>
	<head>
		<title>' . htmlspecialchars($title) . '</title>
		<meta charset="utf-8" />
		<style type="text/css">
		body, form, p, div, hr, fieldset, dl, dt, dd, ul, ol, li, h1, h2, h3, h4, h5, h6 {
			margin: 0;
			padding: 0;
		}
		body {
			font-family: "Trebuchet MS", Arial, Helvetica, Sans-serif;
			padding: .8em;
			background: #eee;
		}
		</style>
		<link rel="stylesheet" type="text/css" href="../../../content.css" />
	</head>
	<body>' . $t . '</body></html>';

	$dest = __DIR__ . '/../src/www/admin/static/doc/' . str_replace('.md', '.html', basename($file));
	file_put_contents($dest, $out);
}
