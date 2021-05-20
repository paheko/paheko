<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

class Render
{

	const FORMAT_SKRIV = 'skriv';
	const FORMAT_ENCRYPTED = 'skriv/encrypted';
	const FORMAT_MARKDOWN = 'markdown';

	static public function render(string $format, File $file, string $content = null, array $options = [])
	{
		if ($format == self::FORMAT_SKRIV) {
			$r = new Skriv;
		}
		else if ($format == self::FORMAT_ENCRYPTED) {
			$r = new EncryptedSkriv;
		}
		else if ($format == self::FORMAT_MARKDOWN) {
			$r = new Markdown;
		}
		else {
			throw new \LogicException('Invalid format: ' . $format);
		}

		return $r->render($file, $content, $options);
	}
}
