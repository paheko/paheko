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
		if (null === $content && $this->file) {
			$content = $this->file->fetch();
		}

		if (empty($content)) {
			return $content;
		}

		$md = Markdown_Parser::instance();
		Markdown_Extensions::register($md);

		// Register Paheko extensions
		$ext = new Extensions($this);

		foreach ($ext->getList() as $name => $callback) {
			$md->registerExtension($name, $callback);
		}

		$content = $md->text($content);
		unset($md);

		$content = preg_replace_callback(';<a href="([\w_-]+?)">;i', function ($matches) {
			return sprintf('<a href="%s">', htmlspecialchars($this->resolveLink(htmlspecialchars_decode($matches[1]))));
		}, $content);

		return sprintf('<div class="web-content">%s</div>', $content);
	}
}
