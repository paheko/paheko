<?php

use KD2\HTML\Markdown;
use KD2\HTML\Markdown_Extensions;

require_once __DIR__ . '/../src/include/lib/KD2/HTML/Markdown.php';
require_once __DIR__ . '/../src/include/lib/KD2/HTML/Markdown_Extensions.php';

$md = new Markdown;

// Allow extra tags for Markdown quickref
$extra_tags = [
	'blockquote' => null,
	'pre' => null,
	'br' => null,
	'h1' => null,
	'h2' => null,
	'h3' => null,
	'h4' => null,
	'h5' => null,
	'h6' => null,
	'ul' => null,
	'ol' => null,
	'li' => null,
	'table' => null,
	'thead' => null,
	'tbody' => null,
	'tr' => null,
	'th' => null,
	'td' => null,
	'hr' => null,
	'div' => ['style'],
];

Markdown_Extensions::register($md);

foreach (glob(__DIR__ . '/../doc/admin/*.md') as $file) {
	if (basename($file) == 'markdown_quickref.md') {
		$md->allowed_inline_tags = array_merge($md->allowed_inline_tags, $extra_tags);
	}
	else {
		$md->allowed_inline_tags = $md::DEFAULT_INLINE_TAGS;
	}

	$t = file_get_contents($file);

	if (preg_match('/^Title: (.*)/', $t, $match)) {
		$t = substr($t, strlen($match[0]));
	}

	$t = $md->text($t);
	$t = preg_replace('!(<a\s+[^>]+external[^>]+)>!', '$1 target="_blank">', $t);

	$title = $match[1] ?? $file;

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
		.web-content .nav ul {
			list-style-type: none;
			margin: -.8em;
			margin-bottom: 1em;
			padding: 1em;
			background: #ddd;
			border-bottom: 1px solid #999;
			text-align: center;
		}
		.web-content .boutons ul {
			list-style-type: none;
			background: #ccc;
			padding: .5em;
			margin: 0;
		}
		.web-content .nav li, .web-content .boutons li {
			display: inline-block;
			margin: 0 1em;
		}
		.web-content .nav a, .web-content .boutons a {
			display: inline-block;
			background: #fff;
			color: darkblue;
			border-radius: .2em;
			padding: .3em .5em;
			font-size: 1.2em;
		}
		.web-content .nav strong a {
			color: darkred;
			box-shadow: 0px 0px 5px orange;
		}
		</style>
		<link rel="stylesheet" type="text/css" href="../../../content.css" />
	</head>
	<body><div class="web-content">' . $t . '</div></body></html>';

	$dest = __DIR__ . '/../src/www/admin/static/doc/' . str_replace('.md', '.html', basename($file));
	file_put_contents($dest, $out);
}
