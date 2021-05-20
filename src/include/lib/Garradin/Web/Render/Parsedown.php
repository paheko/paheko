<?php

namespace Garradin\Web\Render;

//use Parsedown;
use ParsedownExtra;

use Garradin\Entities\Files\File;

use Garradin\Utils;

/**
 * Custom Parsedown extension to enable the use of Skriv extensions inside Markdown markup
 *
 * @see https://github.com/erusev/parsedown/wiki/Tutorial:-Create-Extensions
 */
class Parsedown extends ParsedownExtra
{
	protected $skriv;
	protected $rawTextTOC;

	function __construct(?File $file)
	{
		$this->BlockTypes['<'][] = 'SkrivExtension';
		$this->BlockTypes['['][]= 'TOC';

		parent::__construct();
		$this->skriv = new Skriv;

		if ($file) {
			$this->skriv->isRelativeTo($file);
		}
	}

	protected function blockSkrivExtension(array $line): ?array
	{
		$line = $line['text'];

		if (strpos($line, '<<') === 0 && preg_match('/^<<<?([a-z_]+)((?:(?!>>>?).)*?)(>>>?$|$)/i', trim($line), $match)) {
			$text = $this->skriv->callExtension($match);

			return [
				'char'    => $line[0],
				'element' => [
					'name'                   => 'div',
					'rawHtml'                => $text,
					'allowRawHtmlInSafeMode' => true,
				],
				'complete' => true,
			];
		}

		return null;
	}

	protected function blockHeader($line)
	{
		$block = parent::blockHeader($line);

		if (is_array($block)) {
			if (!isset($block['element']['attributes']['id'])) {
				$block['element']['attributes']['id'] = Utils::transformTitleToURI($block['element']['text']);
			}

			$level = substr($block['element']['name'], 1); // h1, h2... -> 1, 2...
			$id = $block['element']['attributes']['id'];
			$label = $block['element']['text'];

			$this->toc[] = compact('level', 'id', 'label');
		}

		return $block;
	}

	protected function blockTOC(array $line): ?array
	{
		if (!preg_match('/^\[(?:toc|sommaire)\]$/', trim($line['text']))) {
			return null;
		}

		return [
			'char'     => $line['text'][0],
			'complete' => true,
			'element'  => [
				'name'                   => 'div',
				'rawHtml'                => '<toc></toc>',
				'allowRawHtmlInSafeMode' => true,
			],
		];
	}

	public function buildTOC(): string
	{
		if (!count($this->toc)) {
			return '';
		}

		$out = '<div class="toc">';

		$level = 0;

		foreach ($this->toc as $h) {
			if ($h['level'] > $level) {
				$out .= str_repeat('<ol>', $h['level'] - $level);
				$level = $h['level'];
			}
			elseif ($h['level'] < $level) {
				$out .= str_repeat('</ol>', $level - $h['level']);
				$level = $h['level'];
			}

			$out .= sprintf('<li><a href="#%s">%s</a></li>', $h['id'], $h['label']);
		}

		if ($level > 0) {
			$out .= str_repeat('</ol>', $level);
		}

		$out .= '</div>';

		return $out;
	}

	public function text($text)
	{
		$out = parent::text($text);

		if (false !== strpos($out, '<toc></toc>')) {
			$toc = $this->buildTOC();
			$out = str_replace('<toc></toc>', $toc, $out);
		}

		return $out;
	}
}