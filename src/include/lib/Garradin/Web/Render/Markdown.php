<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

use Garradin\Squelette_Filtres;
use Garradin\Plugin;
use Garradin\Utils;
use Garradin\Files\Files;
use Garradin\UserTemplate\CommonModifiers;

use Parsedown;
use Parsedown_Extra;

use const Garradin\{ADMIN_URL, WWW_URL};

class Markdown
{
	static protected $parsedown;

	public function render(?File $file, ?string $content = null, array $options = []): string
	{
		if (!self::$parsedown)
		{
			self::$parsedown = new ParsedownExtra;
			self::$parsedown->setBreaksEnabled(true);
			self::$parsedown->setUrlsLinked(true);
			self::$parsedown->setSafeMode(true);
		}

		$str = $content ?? $file->fetch();

		$str = self::$parsedown->text($str);

		$str = CommonModifiers::typo($str);

		$str = preg_replace_callback(';<a href="((?!https?://|\w+:).+)">;i', function ($matches) {
			return sprintf('<a href="%s" target="_parent">', $this->resolveLink($matches[1]));
		}, $str);

		return sprintf('<div class="web-content">%s</div>', $str);
	}
}
