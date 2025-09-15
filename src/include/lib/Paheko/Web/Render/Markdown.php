<?php

namespace Paheko\Web\Render;

use Paheko\UserTemplate\CommonModifiers;
use Paheko\UserTemplate\Modules;
use Paheko\Utils;
use Paheko\Users\Session;

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

	public function renderUncached(?string $content = null): string
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

			self::$md->registerDefaultExtensionCallback([self::class, 'defaultExtensionCallback']);
		}

		Extensions::setRenderer($this);

		return self::$md->text($content);
	}

	static public function defaultExtensionCallback(bool $block, array $params, ?string $content, string $name, KD2_Markdown $md): string
	{
		$args = compact('block', 'params', 'content');

		$out = Modules::snippetsAsString(sprintf(Modules::SNIPPET_MARKDOWN_EXTENSION, $name), $args);

		if (!$out) {
			return $md::error(sprintf("L'extension <<%s>> n'existe pas.", $name), $block);
		}

		return $out;
	}
}
