<?php

namespace Garradin\Web\Render;

use Parsedown as Parent_Parsedown;

use Garradin\Entities\Files\File;
use Garradin\UserTemplate\CommonModifiers;

use Garradin\Utils;

/**
 * Custom Parsedown extension to enable the use of extensions inside Markdown markup
 *
 * Also adds support for footnotes and Table of Contents
 *
 * @see https://github.com/erusev/parsedown/wiki/Tutorial:-Create-Extensions
 */
class Parsedown extends Parent_Parsedown
{
	protected $extensions;
	public $toc = [];

	/**
	 * Custom tags allowed inline
	 */
	const ALLOWED_INLINE_TAGS = [
		'kbd'    => null,
		'samp'   => null,

		'del'    => null,
		'ins'    => null,
		'sup'    => null,
		'sub'    => null,

		'mark'   => null,
		'var'    => null,
	];

	/**
	 * Custom tags allowed as blocks
	 */
	const ALLOWED_BLOCK_TAGS = [
		'object' => ['type', 'width', 'height', 'data'],
		'iframe' => ['src', 'width', 'height', 'frameborder', 'scrolling', 'allowfullscreen'],
		'audio'  => ['src', 'controls', 'loop'],
		'video'  => ['src', 'controls', 'width', 'height', 'poster'],
	];

	public function setExtensions(Extensions $ext)
	{
		$this->extensions = $ext;
	}

	function __construct()
	{
		array_unshift($this->BlockTypes['<'], 'Extension');
		array_unshift($this->BlockTypes['<'], 'TOC');

		$this->BlockTypes['['][]= 'TOC';
		$this->BlockTypes['{'][]= 'TOC';
		$this->BlockTypes['{'][]= 'Class';

		// Make Skriv extensions also available inline, before anything else
		array_unshift($this->InlineTypes['<'], 'Extension');

		# identify footnote definitions before reference definitions
		array_unshift($this->BlockTypes['['], 'Footnote');

		# identify footnote markers before before links
		array_unshift($this->InlineTypes['['], 'FootnoteMarker');

		$this->InlineTypes['='][] = 'Highlight';

		$this->inlineMarkerList .= '=';
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

	/**
	 * Filter attributes for a HTML tag
	 */
	protected function _filterHTMLAttributes(string $name, ?array $allowed, string $str): ?array
	{
		$attributes = $this->_parseAttributes($str);

		$allowed ??= [];
		$allowed[] = 'class';
		$allowed[] = 'lang';
		$allowed[] = 'title';

		foreach ($attributes as $key => $value) {
			if (!in_array($key, $allowed)) {
				unset($attributes[$key]);
				continue;
			}

			$value = $value ? htmlspecialchars($value) : '';
			$attributes[$key] = $value;
		}

		if ($name == 'iframe' || $name == 'video' || $name == 'audio') {
			$attributes['loading'] = 'lazy';
		}

		if ($name == 'iframe' || $name == 'object') {
			if (!isset($attributes['src']) || !preg_match('!^https?://!', $attributes['src'])) {
				return null;
			}

			$attributes['referrerpolicy'] = 'no-referrer';
			$attributes['sandbox'] = 'allow-same-origin allow-scripts';
		}

		return $attributes;
	}

	/**
	 * Inline extensions: <<color red>>bla blabla<</color>>
	 */
	protected function inlineExtension(array $str): ?array
	{
		if (preg_match('/^<<<?(\/?[a-z_]+)((?:(?!>>>?).)*?)>>>?/i', $str['text'], $match)) {
			$text = $this->extensions->call($this, $match[1], false, $match[2]);

			return [
				'extent'    => strlen($match[0]),
				'element' => [
					'rawHtml'                => $text,
					'allowRawHtmlInSafeMode' => true,
				],
			];
		}

		return null;
	}

	/**
	 * Block extensions
	 */
	protected function blockExtension(array $line): ?array
	{
		$line = $line['text'];

		if (strpos($line, '<<') === 0 && preg_match('/^<<<?(\/?[a-z_]+)((?:(?!>>>?).)*?)(>>>?$|$)/is', trim($line), $match)) {
			$text = $this->extensions->call($this, $match[1], true, $match[2]);

			return [
				'char'    => $line[0],
				'element' => [
					'rawHtml'                => $text,
					'allowRawHtmlInSafeMode' => true,
				],
				'complete' => true,
			];
		}

		return null;
	}

	/**
	 * Class block:
	 * {{class1 class2
	 * > My block
	 * }}
	 */
	protected function blockClass(array $line): ?array
	{
		$line = $line['text'];

		if (strpos($line, '{{{') === 0) {
			$classes = trim(substr($line, 3));
			$classes = str_replace('.', '', $classes);

			return [
				'char'    => $line[0],
				'element' => [
					'name' => 'div',
					'attributes' => ['class' => $classes],
				],
				'closed' => false,
			];
		}

		return null;
	}

	protected function blockClassContinue(array $line, array $block): ?array
	{
		if (isset($block['closed'])) {
			return null;
		}

		if (strpos($line['text'], '}}}') !== false) {
			$block['closed'] = true;
		}

		return $block;
	}

	/**
	 * Remove HTML comments
	 * @replaces parent::blockComment
	 */
	protected function blockComment($line): ?array
	{
		if (strpos($line['text'], '<!--') === 0) {
			$block = ['element' => ['rawHtml' => '']];

			if (strpos($line['text'], '-->') !== false) {
				$block['closed'] = true;
			}

			return $block;
		}

		return null;
	}

	/**
	 * Remove HTML comments
	 * @replaces parent::blockComment
	 */
	protected function blockCommentContinue($line, array $block): ?array
	{
		if (isset($block['closed'])) {
			return null;
		}

		if (strpos($line['text'], '-->') !== false) {
			$block['closed'] = true;
		}

		return $block;
	}

	/**
	 * Transform ==text== to <mark>text</mark>
	 */
	protected function inlineHighlight(array $str): ?array
	{
		if (substr($str['text'], 1, 1) === '='
			&& preg_match('/^==(?=\S)(.+?)(?<=\S)==/', $str['text'], $matches))
		{
			return [
				'extent' => strlen($matches[0]),
				'element' => [
					'name' => 'mark',
					'handler' => [
						'function' => 'lineElements',
						'argument' => $matches[1],
						'destination' => 'elements',
					],
				],
			];
		}

		return null;
	}

	/**
	 * Override default strikethrough, as it is incorrectly using <del>
	 */
	protected function inlineStrikethrough($Excerpt)
	{
		if (substr($Excerpt['text'], 1, 1) === '~' && preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $Excerpt['text'], $matches))
		{
			return array(
				'extent' => strlen($matches[0]),
				'element' => array(
					'name' => 'span',
					'attributes' => ['style' => 'text-decoration: line-through'],
					'handler' => array(
						'function' => 'lineElements',
						'argument' => $matches[1],
						'destination' => 'elements',
					)
				),
			);
		}

		return null;
	}

	/**
	 * Allow simple inline markup tags
	 */
	protected function inlineMarkup($str)
	{
		$text = $str['text'];

		// Comments
		if (preg_match('/<!--.*?-->/', $text, $match)) {
			return ['element' => ['rawHtml' => ''], 'extent', strlen($match[0])];
		}

		// Skip if not a tag
		if (!preg_match('!(</?)(\w+)([^>]*?)>!', $text, $match)) {
			return null;
		}
		$name = $match[2];

		if (!array_key_exists($name, self::ALLOWED_INLINE_TAGS)) {
			return null;
		}

		$attributes = $this->_filterHTMLAttributes($name, self::ALLOWED_INLINE_TAGS[$name], $match[2]);

		return [
			'element' => [
				'rawHtml' => $match[1] . $name . '>',
				'allowRawHtmlInSafeMode' => true,
				'attributes' => $attributes,
			],
			'extent' => strlen($match[0]),
		];
	}

	/**
	 * Allow some markup blocks, eg. iframe
	 */
	protected function blockMarkup($line): ?array
	{
		// Skip if not a tag
		if (!preg_match('!<(/?)(\w+)([^>]*)>!', $line['text'], $match)) {
			return null;
		}

		$name = $match[2];

		if (!array_key_exists($name, self::ALLOWED_BLOCK_TAGS)) {
			return null;
		}

		// Don't load youtube player, just display preview
		if ($name == 'iframe' && preg_match('!https://www.youtube.com/embed/([^"]+)!', $line['text'], $m)) {
			return [
				'element' => [
					'rawHtml' => sprintf('<figure class="video"><a href="https://www.youtube.com/watch?v=%s" target="_blank" title="Ouvrir la vidéo" rel="noreferrer"><img width=320 height=180 src="http://img.youtube.com/vi/%1$s/mqdefault.jpg" alt="Vidéo Youtube" loading="lazy" /></a></figure>', htmlspecialchars($m[1])),
					'allowRawHtmlInSafeMode' => true,
				],
			];
		}

		$attributes = $this->_filterHTMLAttributes($name, self::ALLOWED_BLOCK_TAGS[$name], $match[3]);

		if (null === $attributes) {
			return null;
		}

		return [
			'element' => [
				'name' => $name,
				'attributes' => $attributes,
				'autobreak' => true,
				'text' => '',
			],
		];
	}

	/**
	 * Open external links in new page
	 */
	protected function inlineLink($Excerpt)
	{
		$e = parent::inlineLink($Excerpt);

		if (strstr($e['element']['attributes']['href'], ':')) {
			$e['element']['attributes']['target'] = '_blank';
			$e['element']['attributes']['rel'] = 'nofollow,noreferrer';
		}

		return $e;
	}

	/**
	 * Add typo modifier to text
	 */
	protected function inlineText($text)
	{
		$text = CommonModifiers::typo($text);
		return parent::inlineText($text);
	}

	/**
	 * Use headers to populate TOC
	 */
	protected function blockHeader($line): ?array
	{
		$block = parent::blockHeader($line);

		if (!is_array($block)) {
			return $block;
		}

		$text =& $block['element']['handler']['argument'];

		// Extract attributes: {#id} {.class-name}
		if (preg_match('/(?!\\\\)[ #]*{((?:[#.][-\w]+[ ]*)+)}[ ]*$/', $text, $matches, PREG_OFFSET_CAPTURE)) {
			$block['element']['attributes'] = $this->parseAttributeData($matches[1][0]);
			$text = trim(substr($text, 0, $matches[0][1]));
		}

		if (strstr($block['element']['attributes']['class'] ?? '', 'no_toc')) {
			return $block;
		}

		if (!isset($block['element']['attributes']['id'])) {
			$block['element']['attributes']['id'] = strtolower(Utils::transformTitleToURI($text));
		}

		$level = substr($block['element']['name'], 1); // h1, h2... -> 1, 2...
		$id = $block['element']['attributes']['id'];
		$label = $text;
		unset($text);

		$this->toc[] = compact('level', 'id', 'label');

		return $block;
	}

	protected function parseAttributeData(string $string): array
	{
		$data = [];
		$classes = [];
		$attributes = preg_split('/[ ]+/', $string, - 1, PREG_SPLIT_NO_EMPTY);

		foreach ($attributes as $attribute) {
			if ($attribute[0] === '#') {
				$data['id'] = substr($attribute, 1);
			}
			else {
				$classes[] = substr($attribute, 1);
			}
		}

		if (count($classes))  {
			$data['class'] = implode(' ', $classes);
		}

		return $data;
	}

	/**
	 * Replace [toc], <<toc>>, {:toc} and [[_TOC_]] with temporary TOC token
	 * as we first need to process the headings to build the TOC
	 */
	protected function blockTOC(array $line): ?array
	{
		if (!preg_match('/^(?:\[toc\]|\{:toc\}|\[\[_TOC_\]\]|<<<?toc(?:\s+([^>]+?))?>>>?)$/', trim($line['text']), $match)) {
			return null;
		}

		$level = 0;

		if (!empty($match[1]) && false !== ($pos = strpos($match[1], 'level='))) {
			$level = (int) trim(substr($match[1], 6 + $pos, 2), ' "');
		}

		$aside = (bool) strstr($match[1] ?? '', 'aside');

		return [
			'char'     => $line['text'][0],
			'complete' => true,
			'element'  => [
				'rawHtml'                => $this->extensions->getTempTOC(false, compact('level', 'aside')),
				'allowRawHtmlInSafeMode' => true,
			],
		];
	}

	/**
	 * Footnotes implementation, inspired by ParsedownExtra
	 * We're not using ParsedownExtra as it's buggy and unmaintained
	 */
	protected function blockFootnote(array $line): ?array
	{
		if (preg_match('/^\[\^(.+?)\]:[ ]?(.*)$/', $line['text'], $matches))
		{
			$block = array(
				'footnotes' => [$matches[1] => $matches[2]],
			);

			return $block;
		}

		return null;
	}

	protected function blockFootnoteContinue(array $line, array $block): ?array
	{
		if ($line['text'][0] === '[' && preg_match('/^\[\^(.+?)\]: ?(.*)$/', $line['text'], $matches))
		{
			$block['footnotes'][$matches[1]] = $matches[2];
			return $block;
		}

		end($block['footnotes']);
		$last = key($block['footnotes']);

		if (isset($block['interrupted']) && $line['indent'] >= 4)
		{
			$block['footnotes'][$last] .= "\n\n" . $line['text'];

			return $block;
		}
		else
		{
			$block['footnotes'][$last] .= "\n" . $line['text'];

			return $block;
		}
	}

	protected function blockFootnoteComplete(array $in)
	{
		$html = '';

		foreach ($in['footnotes'] as $name => $value) {
			$html .= sprintf('<dt id="fn-%s"><a href="#fn-ref-%1$s">%1$s</a></dt><dd>%s</dd>', htmlspecialchars($name), $this->line($value));
		}

		$out = [
			'complete' => true,
			'element' => [
				'name'                   => 'dl',
				'attributes'             => ['class' => 'footnotes'],
				'rawHtml'                => $html,
				'allowRawHtmlInSafeMode' => true,
			],
		];

		return $out;
	}


	protected function inlineFootnoteMarker($Excerpt)
	{
		if (preg_match('/^\[\^(.+?)\]/', $Excerpt['text'], $matches))
		{
			$name = htmlspecialchars($matches[1]);

			$Element = array(
				'name' => 'a',
				'attributes' => ['id' => 'fn-ref-'.$name, 'href' => '#fn-'.$name, 'class' => 'footnote-ref'],
				'text' => $name,
			);

			return [
				'extent' => strlen($matches[0]),
				'element' => $Element,
			];
		}
	}


	public function text($text)
	{
		$this->toc = [];

		$out = parent::text($text);

		$out = $this->extensions->replaceTempTOC($out, $this->toc);
		return $out;
	}
}
