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

class Blocks
{
	const SEPARATOR = "\n\n====\n\n";

	static protected $parsedown;
	protected $_stack = [];

	// Grid columns templates
	// CSS grid template => Number of columns
	const COLUMNS_TEMPLATES = [
		'none' => 1, // No columns
		'none / 1fr 1fr' => 2,
		'none / 1fr 1fr 1fr' => 3,
		'none / 1fr 1fr 1fr 1fr' => 4,
		'none / .5fr 1fr' => 2,
		'none / 1fr .5fr' => 2,
	];

	public function render(?string $content = null): string
	{
		$content = preg_replace("/\r\n?/", "\n", $content);
		$out = '<div class="web-blocks">';

		foreach (explode(self::SEPARATOR, $content) as $block) {
			$out .= $this->block($block);
		}

		foreach ($this->_stack as $type) {
			$out .= '</article></section>';
		}

		$out .= '</div>';

		return $out;
	}

	protected function block(string $block): string
	{
		@list($header, $content) = explode("\n\n", $block, 2);
		$out  = '';

		$meta = [];

		foreach (explode("\n", $header) as $line) {
			$key = strtolower(trim(strtok($line, ':')));
			$value = trim(strtok(''));
			$meta[$key] = $value;
		}

		if (empty($meta['type'])) {
			throw new \InvalidArgumentException('No type specified in block');
		}

		$type = $meta['type'];
		$content = trim($content ?? '', "\n");
		$class = sprintf('web-block-%s', $type);

		switch ($type) {
			case 'columns':
				if (array_pop($this->_stack)) {
					$out .= '</article></section>';
				}

				$out .= sprintf('<section class="web-columns" %s>',
					isset($meta['grid-template'])
						? sprintf('style="--grid-template: %s"', htmlspecialchars($meta['grid-template']))
						: '');
				return $out;
			case 'column':
				if (array_pop($this->_stack)) {
					$out .= '</article>';
				}

				$out .= '<article class="web-column">';
				$this->_stack[] = 'column';
				return $out;
			case 'code':
				return sprintf('<pre class="%s">%s</pre>', $class, htmlspecialchars($content));
			case 'markdown':
				$md = new Markdown;
				return sprintf('<div class="web-content %s">%s</div>', $class, $md->render($content));
			case 'skriv':
				$skriv = new Skriv;
				return sprintf('<div class="web-content %s">%s</div>', $class, $skriv->render($content));
			case 'image':
				return sprintf('<div class="%s">%s</div>', $class, $this->image($content, $meta));
			case 'gallery':
				return sprintf('<div class="%s">%s</div>', $class, $this->gallery($content, $meta));
			case 'heading':
				return sprintf('<h2 class="%s">%s</h2>', $class, htmlspecialchars($content));
			case 'quote':
				return sprintf('<blockquote class="%s"><p>%s</p></blockquote>', $class, nl2br(htmlspecialchars($content)));
			case 'video':
				return sprintf('<iframe class="%s" src="%s" frameborder="0" allow="accelerometer; encrypted-media; gyroscope" allowfullscreen></iframe>', $class, htmlspecialchars($content));
			default:
				throw new \LogicException('Unknown type: ' . $type);
		}
	}

	public function image(string $content, array $meta): string
	{
		$url = WWW_URL . trim($content);
		$size = intval($meta['size'] ?? 0);

		$caption = !empty($meta['caption']) ? sprintf('<figcaption>%s</figcaption>', htmlspecialchars(trim($meta['caption']))) : '';

		if (!empty($meta['size'])) {
			return sprintf(
				'<figure><a href="%s"><img src="%s" alt="%s" /></a>%s</figure>',
				htmlspecialchars($url),
				htmlspecialchars(sprintf('%s?%dpx', $url, $size)),
				htmlspecialchars(trim($meta['caption'] ?? '')),
				$caption
			);
		}
		else {
			return sprintf(
				'<figure><img src="%s" alt="%s" />%s</figure>',
				$url,
				htmlspecialchars(trim($meta['caption'] ?? '')),
				$caption
			);
		}
	}

	public function gallery(string $content, array $meta): string
	{
		$images = explode("\n", trim($content));
		$out = '';

		foreach ($images as $image) {
			$image = explode('=', $image);
			$out .= $this->image($image[0], ['size' => 200, 'caption' => $image[1] ?? '']);
		}

		return $out;
	}
}
