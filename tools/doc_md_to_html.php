<?php

use KD2\HTML\Markdown;
use KD2\HTMLDocument;
use KD2\HTML\Markdown_Extensions;

require_once __DIR__ . '/../src/include/lib/KD2/HTMLDocument.php';
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

	// rewrite API HTML to make it better
	if (basename($file) === 'api.md') {
		$dom = new HTMLDocument;
		$dom->loadHTML($t);

		foreach ($dom->querySelectorAll('h3') as $route) {
			$label = null;
			$content = [];

			$next = $route;

			while ($next = $next->nextElementSibling) {
				if ($next->tagName === 'h3' || $next->tagName === 'h2' || $next->tagName === 'h1') {
					break;
				}

				if ($next->tagName === 'p' && null === $label) {
					$label = $next;
				}
				else {
					$content[] = $next;
				}
			}

			foreach ($content as $key => $elm) {
				$content[$key] = $elm->cloneNode(true);
				$elm->parentNode->removeChild($elm);
			}

			$parent = $dom->createElement('details');
			$parent->setAttribute('class', 'api');
			$title = $route->cloneNode(true);
			$method = strtok($title->textContent, ' ');
			$path = strtok('');

			$summary = $dom->createElement('summary');
			$summary->setAttribute('id', $route->getAttribute('id'));
			$summary->setAttribute('onclick', 'if (!this.parentNode.open) window.history.replaceState(null, \'\', \'#\' + this.id); return true;');

			if (in_array($method, ['GET', 'POST', 'PUT', 'DELETE'])) {
				$label->parentNode->removeChild($label);

				$path = '/' . trim($path, '/');
				$path = preg_replace('/(\{[^}]+\})/', '<u>$1</u>', $path);
				$fragment = $dom->createDocumentFragment();
				$fragment->appendXML($path);
				$path = $dom->createElement('code');
				$path->appendChild($fragment);

				$m = $dom->createElement('b', $method);
				$m->setAttribute('class', 'method-' . $method);
				$summary->replaceChildren($m, ' ', $path, ' ', $dom->createElement('span', $label->textContent));
			}
			else {
				array_unshift($content, $label);
				$summary->replaceChildren($dom->createElement($title->tagName, $title->textContent));
			}

			$parent->appendChild($summary);

			foreach ($content as $elm) {
				$parent->appendChild($elm);
			}

			$route->replaceWith($parent);
		}

		$t = '<style type="text/css">
		details.api {
			list-style: none;
			padding: 0.2em 0.5em;
			transition: background-color .2s;
			background: #fff;
			padding: 0;
			border: 1px solid #ccc;
			margin-bottom: .7em;
			border-radius: .5rem;
		}

		details.api summary {
			cursor: pointer;
			display: flex;
			align-items: center;
			gap: .8rem;
			font-size: 1.2em;
			position: relative;
			padding: .5rem;
			padding-right: 2em;
			flex-wrap: wrap;
		}

		details.api summary::after {
			content: "⌄";
			position: absolute;
			right: .5rem;
			bottom: .5em;
			font-size: 2em;
			line-height: .5em;
			transition: top .2s, transform .4s, color .2s;
		}

		details.api summary:hover::after {
			color: darkred;
			text-shadow: 0px 0px 5px orange;
		}

		details.api:not([open]):hover {
			background: #eee;
			box-shadow: 0px 0px 5px orange;
		}

		details.api[open] summary::after {
			transform: rotate(180deg);
			top: .75em;
			right: 0;
		}

		details.api[open] {
			padding: .5rem;
		}

		details.api[open] summary {
			margin-bottom: 1em;
			padding: 0;
			padding-right: 2em;
		}

		details.api summary b {
			display: block;
			border-radius: .3em;
			background: #333;
			padding: .1rem .4rem;
			color: #fff;
			width: 8ch;
			text-align: center;
		}

		details.api summary code {
			background: none;
			font-weight: bold;
			word-break: keep-all;
		}

		details.api summary code u {
			text-decoration: none;
			border: 1px dashed #999;
			color: darkblue;
			border-radius: .5rem;
			padding: .2rem;
		}

		details.api summary span {
			font-size: 1rem;
		}

		details.api summary b.method-GET {
			background: #8fbc8f;
		}
		details.api summary b.method-POST {
			background: #4682b4;
		}
		details.api summary b.method-PUT {
			background: #9370db;
		}
		details.api summary b.method-DELETE {
			background: #cd5c5c;
		}

		details.api summary h3 {
			margin: 0;
		}

		details.api.all {
			float: right;
		}

		details.api.all summary {
			margin: 0;
			font-size: .9rem;
		}

		@media screen and (max-width: 800px) {
			details.api summary {
				flex-direction: column;
				align-items: start;
			}

			details.api.all {
				float: none;
			}
		}
		</style>
		<details class="api all"><summary onclick="var open = !this.parentNode.hasAttribute(\'open\'); document.querySelectorAll(\'details\').forEach(elm => elm.open = open); return false;">Tout déplier / replier</summary></details>';
		$t .= $dom->saveHTML();
	}

	$title = $match[1] ?? $file;

	$out = '<!DOCTYPE html>
	<html>
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title>' . htmlspecialchars($title) . '</title>
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
