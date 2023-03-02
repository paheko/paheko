<?php

namespace Garradin\Web\Render;

class Markdown extends AbstractRender
{
	/**
	 * Used by doc_md_to_html.php script
	 */
	public $toc = [];

	public function render(?string $content = null): string
	{
		$parsedown = Parsedown::instance();
		$parsedown->setBreaksEnabled(true);
		$parsedown->setUrlsLinked(true);
		$parsedown->setSafeMode(true);

		$str = $content ?? $this->file->fetch();

		$ext = new Extensions($this);
		$parsedown->setExtensions($ext);

		$str = $parsedown->text($str);
		$this->toc = $parsedown->toc;
		unset($parsedown);

		$str = preg_replace_callback(';<a href="([\w_-]+?)">;i', function ($matches) {
			return sprintf('<a href="%s">', htmlspecialchars($this->resolveLink(htmlspecialchars_decode($matches[1]))));
		}, $str);

		return sprintf('<div class="web-content">%s</div>', $str);
	}
}
