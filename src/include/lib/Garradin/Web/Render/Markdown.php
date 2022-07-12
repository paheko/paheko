<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

use Garradin\Plugin;
use Garradin\Utils;
use Garradin\Files\Files;
use Garradin\UserTemplate\CommonModifiers;

use const Garradin\{ADMIN_URL, WWW_URL};

class Markdown extends AbstractRender
{
	public function render(?string $content = null): string
	{
		$parsedown = new Parsedown($this->file, $this->user_prefix);
		$parsedown->setBreaksEnabled(true);
		$parsedown->setUrlsLinked(true);
		$parsedown->setSafeMode(true);

		$str = $content ?? $this->file->fetch();

		$str = $parsedown->text($str);

		$str = CommonModifiers::typo($str);

		$str = preg_replace_callback(';<a href="((?!https?://|\w+:|#).+)">;i', function ($matches) {
			return sprintf('<a href="%s" target="_parent">', htmlspecialchars($this->resolveLink($matches[1])));
		}, $str);

		return sprintf('<div class="web-content">%s</div>', $str);
	}
}
