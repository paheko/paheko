<?php

namespace Paheko\Web\Render;

use Paheko\UserTemplate\CommonModifiers;

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

	static protected $md = null;

	public function render(string $content = null): string
	{
		if (empty($content)) {
			return '';
		}

		if (!isset(self::$md)) {
			self::$md = Markdown_Parser::instance();
			Markdown_Extensions::register(self::$md);

			// Register Paheko extensions
			foreach (Extensions::getList() as $name => $callback) {
				self::$md->registerExtension($name, $callback);
			}
		}

		Extensions::setRenderer($this);

		$content = self::$md->text($content);

		return $this->outputHTML($content);
	}
}
