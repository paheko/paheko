<?php

namespace Paheko;

use Paheko\UserTemplate\Modules;

$m = Modules::get('web');

if ($m) {
	$files = ['article.html', 'category.html'];

	foreach ($files as $name) {
		if ($file = $m->getLocalFile($name)) {
			$text = $file->fetch();
			$replaced = preg_replace('/(?<!_)(?:documents|gallery)\.html/', '_$0', $text);

			if ($replaced === $text) {
				continue;
			}

			$file->setContent($replaced);
		}
	}
}
