<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

use Garradin\Plugin;
use Garradin\Utils;
use Garradin\Files\Files;

use const Garradin\{ADMIN_URL, WWW_URL};

class Markdown extends AbstractRender
{
	public function render(?string $content = null): string
	{
		$parsedown = Parsedown::instance();
		$parsedown->setBreaksEnabled(true);
		$parsedown->setUrlsLinked(true);
		$parsedown->setSafeMode(true);

		$str = $content ?? $this->file->fetch();

		$ext = new Extensions($this);
		$parsedown->setExtensions($ext);

		$str = $parsedown->text($str);
		unset($parsedown);

		$str = preg_replace_callback(';<a href="((?!https?://|\w+:|#).+?)">;i', function ($matches) {
			return sprintf('<a href="%s" target="_parent">', htmlspecialchars($this->resolveLink(htmlspecialchars_decode($matches[1]))));
		}, $str);

		return sprintf('<div class="web-content">%s</div>', $str);
	}
}
