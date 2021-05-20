<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

use Garradin\Plugin;
use Garradin\Utils;
use Garradin\Files\Files;
use Garradin\UserTemplate\CommonModifiers;

use const Garradin\{ADMIN_URL, WWW_URL};

class Markdown
{
	use AttachmentAwareTrait;

	public function render(?File $file, ?string $content = null, array $options = []): string
	{
		$parsedown = new Parsedown($file);
		$parsedown->setBreaksEnabled(true);
		$parsedown->setUrlsLinked(true);
		$parsedown->setSafeMode(true);

		if ($file) {
			$this->isRelativeTo($file);
		}

		$str = $content ?? $file->fetch();

		$str = $parsedown->text($str);

		$str = CommonModifiers::typo($str);

		$str = preg_replace_callback(';<a href="((?!https?://|\w+:|#).+)">;i', function ($matches) {
			var_dump($matches);
			return sprintf('<a href="%s" target="_parent">', $this->resolveLink($matches[1]));
		}, $str);

		return sprintf('<div class="web-content">%s</div>', $str);
	}
}
