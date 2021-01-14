<?php

namespace Garradin\Files\Render;

use Garradin\Entities\Files\File;

use KD2\Garbage2xhtml;

class HTML
{
	static protected $g2x;

	static public function render(File $file, string $content): string
	{
		if (null === self::$g2x) {
			$g2x = self::$g2x = new Garbage2xhtml;
			$g2x->auto_br = false;
		}

		return self::$g2x->process($content);
	}
}
