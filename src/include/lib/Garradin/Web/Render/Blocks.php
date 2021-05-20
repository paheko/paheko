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

/*
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
			grid-gap: 10px;

 */

class Blocks
{
	static protected $parsedown;
	protected $_stack = [];

	public function render(?File $file, ?string $content = null, array $options = []): string
	{
		$str = $content ?? $file->fetch();

		$out = '<div class="web-blocks">';

		// Skip page metadata
		strtok($str, "\n\n----\n\n");

		while ($block = strtok("\n\n----\n\n")) {
			$out .= $this->block($block);
		}

		if ($block = strtok('')) {
			$out .= $this->block($block);
		}

		strtok('', ''); // Free memory

		foreach ($this->_stack as $type) {
			$out .= '</article></section>';
		}

		$out .= '</div>';

		return $out;
	}

	protected function block(string $block): string
	{
		$header = strtok($block, "\n\n");
		$content = strtok('');
		strtok('', ''); // Free memory
		$out  = '';

		$content = trim($content, "\n");
		$class = sprintf('web-block-%s', $type);

		switch ($type) {
			case 'columns':
				if (array_pop($this->_stack)) {
					$out .= '</article></section>';
				}

				$out .= '<section class="web-columns">';
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
				return sprintf('<div class="%s">%s</div>', $this->image($content));
			case 'gallery':
				return sprintf('<div class="%s">%s</div>', $this->gallery($content));
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
}
