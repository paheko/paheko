<?php

namespace Paheko\Web\Render;

/**
 * Converts Markdown text to plaintext for emails
 */
class Plaintext extends AbstractRender
{
	public function renderUncached(?string $content = null): string
	{
		if (empty($content)) {
			return '';
		}

		$content = str_replace(["\r\n", "\r"], "\n", $content);
		$content = str_ireplace(['[toc]', '<<toc>>', '{.no_toc}'], '', $content);

		// Images
		$content = preg_replace_callback('/!\[([^]]*)\]\(([^)]+)\)/', function ($match) {
			$alt = $match[1] ?: 'Image';
			return "\n" . $alt . ': ' . $match[2] . ' ';
		}, $content);

		// Links
		$content = preg_replace_callback('/\[([^]]*)\]\(([^)]+)\)/', function ($match) {
			if ($match[1] === $match[2]) {
				return $match[1];
			}

			return $match[1] . ' (' . $match[2] . ')';
		}, $content);

		// Extensions
		$content = preg_replace_callback('/<<(\/?[a-z]+?)(\s+[^>]*?)?>>/s', function ($match) {
			$name = strtolower($match[1]);

			if ($name === 'button') {
				$attr = $this->_parseAttributes($match[2]);
				return sprintf('%s : %s', $attr['label'] ?? 'Bouton', $attr['href'] ?? '');
			}

			return '';
		}, $content);

		$content = preg_replace("/\n{3,}/", "\n\n", $content);
		$content = wordwrap($content);

		return $content;
	}


	/**
	 * Parse attributes from a HTML tag
	 */
	protected function _parseAttributes(string $str): array
	{
		preg_match_all('/([[:alpha:]][[:alnum:]]*)(?:\s*=\s*(?:([\'"])(.*?)\2|([^>\s\'"]+)))?/i', $str, $match, PREG_SET_ORDER);
		$params = [];

		foreach ($match as $m)
		{
			$params[$m[1]] = isset($m[4]) ? $m[4] : (isset($m[3]) ? $m[3] : null);
		}

		return $params;
	}
}
