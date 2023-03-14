<?php

namespace Garradin\Web\Render;

use Garradin\UserTemplate\CommonModifiers;

use KD2\HTML\Markdown as KD2_Markdown;
use KD2\HTML\Markdown_Extensions;

class Markdown_Parser extends KD2_Markdown
{
	/**
	 * Add typo modifier to text
	 */
	protected function inlineText($text)
	{
		$text = CommonModifiers::typo($text);
		return parent::inlineText($text);
	}
}

class Markdown extends AbstractRender
{
	/**
	 * Used by doc_md_to_html.php script
	 */
	public $toc = [];

	public function render(?string $content = null): string
	{
		$md = Markdown_Parser::instance();
		Markdown_Extensions::register($md);

		// Register Paheko extensions
		$ext = new Extensions($this);

		foreach ($ext->getList() as $name => $callback) {
			$md->registerExtension($name, $callback);
		}

		$str = $content ?? $this->file->fetch();
		$str = $md->text($str);
		unset($md);

		$str = preg_replace_callback(';<a href="([\w_-]+?)">;i', function ($matches) {
			return sprintf('<a href="%s">', htmlspecialchars($this->resolveLink(htmlspecialchars_decode($matches[1]))));
		}, $str);

		return sprintf('<div class="web-content">%s</div>', $str);
	}
}
