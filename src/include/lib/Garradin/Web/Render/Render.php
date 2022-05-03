<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

class Render
{
	const FORMAT_SKRIV = 'skriv';
	const FORMAT_ENCRYPTED = 'skriv/encrypted';
	const FORMAT_MARKDOWN = 'markdown';
	const FORMAT_BLOCKS = 'blocks';

	static protected $attachments = [];

	static public function render(string $format, File $file, string $content = null, string $link_prefix = null)
	{
		return self::getRenderer($format, $file, $link_prefix)->render($content);
	}

	static public function getRenderer(string $format, File $file, string $link_prefix = null)
	{
		if ($format == self::FORMAT_SKRIV) {
			return new Skriv($file, $link_prefix);
		}
		else if ($format == self::FORMAT_ENCRYPTED) {
			return new EncryptedSkriv($file, $link_prefix);
		}
		else if ($format == self::FORMAT_MARKDOWN) {
			return new Markdown($file, $link_prefix);
		}
		else if ($format == self::FORMAT_BLOCKS) {
			return new Blocks($file, $link_prefix);
		}
		else {
			throw new \LogicException('Invalid format: ' . $format);
		}
	}

	static public function registerAttachment(?File $file, string $uri): void
	{
		if (null === $file) {
			return;
		}

		$hash = $file->pathHash();

		if (!array_key_exists($hash, self::$attachments)) {
			self::$attachments[$hash] = [];
		}

		self::$attachments[$hash][$uri] = true;
	}

	static public function listAttachments(File $file) {
		return array_keys(self::$attachments[$file->pathHash()] ?? []);
	}
}
