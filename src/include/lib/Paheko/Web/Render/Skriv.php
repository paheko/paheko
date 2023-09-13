<?php

namespace Paheko\Web\Render;

use Paheko\Entities\Files\File;

use Paheko\Plugins;
use Paheko\UserTemplate\CommonModifiers;

use KD2\SkrivLite;

class Skriv extends AbstractRender
{
	static protected $skriv = null;

	public function render(string $content): string
	{
		$str = $content;

		// Old file URLs, FIXME/TODO remove
		$str = preg_replace_callback('/#file:\[([^\]\h]+)\]/', function ($match) {
			return $this->resolveAttachment($match[1]);
		}, $str);

		if (!isset(self::$skriv)) {
			self::$skriv = new SkrivLite;

			self::$skriv->registerExtensions(Extensions::getList());
		}

		Extensions::setRenderer($this);

		$str = CommonModifiers::typo($str);
		$str = self::$skriv->render($str);

		return $this->outputHTML($content);
	}
}
