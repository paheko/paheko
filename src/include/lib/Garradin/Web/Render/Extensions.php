<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

use Garradin\Plugin;
use KD2\SkrivLite;

class Extensions
{
	public AbstractRender $renderer;
	protected array $list;
	protected array $open_tags = [];

	protected bool $in_grid = false;

	static public function error(string $msg, bool $block = false)
	{
		$tag = $block ? 'p' : 'b';
		return '<' . $tag . ' style="color: red; background: yellow; padding: 5px">/!\ ' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</' . $tag . '>';
	}

	public function __construct(AbstractRender $renderer)
	{
		$this->renderer = $renderer;
		$this->list = $this->getList();
	}

	public function getList(): array
	{
		$list = [
			'file'     => [$this, 'file'],
			'fichier'  => [$this, 'file'],
			'image'    => [$this, 'image'],
			'color'    => [$this, 'color'],
			'bgcolor'  => [$this, 'color'],
			'/color'   => [$this, 'colorClose'],
			'/bgcolor' => [$this, 'colorClose'],
			'toc'      => [$this, 'getTempTOC'],
			'grid'     => [$this, 'grid'],
			'/grid'    => [$this, 'gridClose'],
			'center'   => [$this, 'align'],
			'/center'  => [$this, 'alignClose'],
			'left'     => [$this, 'align'],
			'/left'    => [$this, 'alignClose'],
			'right'    => [$this, 'align'],
			'/right'   => [$this, 'alignClose'],
		];

		Plugin::fireSignal('render.extensions.init', ['extensions' => &$list]);

		return $list;
	}

	public function call($object, string $name, bool $block = false, ?string $params = null, ?string $content = null): string
	{
		$name = strtolower($name);

		if (!array_key_exists($name, $this->list)) {
			return self::error('Unknown extension: ' . $name);
		}

		$params = rtrim($params ?? '');

		// "official" unnamed arguments separated by a pipe
		if ($params !== '' && $params[0] == '|') {
			$params = array_map('trim', explode('|', substr($params, 1)));
		}
		// unofficial named arguments similar to html args
		elseif ($params !== '' && (strpos($params, '=') !== false)) {
			preg_match_all('/([[:alpha:]][[:alnum:]]*)(?:\s*=\s*(?:([\'"])(.*?)\2|([^>\s\'"]+)))?/i', $params, $match, PREG_SET_ORDER);
			$params = [];

			foreach ($match as $m)
			{
				$params[$m[1]] = isset($m[4]) ? $m[4] : (isset($m[3]) ? $m[3] : null);
			}
		}
		// unofficial unnamed arguments separated by spaces
		elseif ($params !== '' && $params[0] == ' ') {
			$params = preg_split('/[ ]+/', $params, -1, \PREG_SPLIT_NO_EMPTY);
		}
		elseif ($params != '') {
			return self::error(sprintf('Invalid arguments (expecting arg1|arg2|arg3… or arg1="value1") for extension "%s": %s', $name, $params));
		}
		else {
			$params = [];
		}

		return call_user_func($this->list[$name], $block, $params, $content, $name, $object);
	}

	public function getTempTOC(bool $block, array $args): string
	{
		return sprintf('<<toc|%d|%d>>', $args['level'] ?? 0, array_key_exists('aside', $args) && $args['aside'] !== false);
	}

	public function replaceTempTOC(string $out, array $toc): string
	{
		if (false !== strpos($out, '<<toc') && preg_match_all('!<<toc\|(\d\|(?:0|1))>>!', $out, $match, PREG_PATTERN_ORDER)) {
			$types = array_unique($match[1] ?? []);

			foreach ($types as $t) {
				$args = ['level' => (int) $t[0], 'aside' => (bool) $t[2]];
				$str = $this->buildTOC($args, $toc);
				$out = str_replace(sprintf('<<toc|%s>>', $t), $str, $out);
			}
		}

		return $out;
	}

	public function buildTOC(array $args, array $toc): string
	{
		$max_level = $args['level'] ?? 0;
		$out = '';

		if (!count($toc)) {
			return $out;
		}

		$level = 0;

		foreach ($toc as $k => $h) {
			if ($max_level > 0 && $h['level'] > $max_level) {
				continue;
			}

			if ($h['level'] < $level) {
				$out .= "\n" . str_repeat("\t", $level);
				$out .= str_repeat("</ol></li>\n", $level - $h['level']);
				$level = $h['level'];
			}
			elseif ($h['level'] > $level) {
				$out .= "\n" . str_repeat("\t", $h['level']);
				$out .= str_repeat("<ol>\n", $h['level'] - $level);
				$level = $h['level'];
			}
			elseif ($k) {
				$out .= "</li>\n";
			}

			$out .= str_repeat("\t", $level + 1);
			$out .= sprintf('<li><a href="#%s">%s</a>', $h['id'], $h['label']);
		}

		if ($level > 0) {
			$out .= "\n";
			$out .= str_repeat('</li></ol>', $level);
		}

		if (isset($args['aside']) && $args['aside'] !== false) {
			$out = '<aside class="toc">' . $out . '</aside>';
		}
		else {
			$out = '<div class="toc">' . $out . '</div>';
		}

		return $out;
	}

	public function file(bool $block, array $args): string
	{
		$name = $args[0] ?? null;
		$caption = $args[1] ?? null;

		if (!$name || !$this->renderer->hasPath()) {
			return self::error('Tag file : aucun nom de fichier indiqué.');
		}

		if (empty($caption)) {
			$caption = substr($name, 0, strrpos($name, '.'));
		}

		$url = $this->renderer->resolveAttachment($name);
		$ext = substr($name, strrpos($name, '.')+1);

		return sprintf(
			'<aside class="file" data-type="%s"><a href="%s" class="internal-file"><b>%s</b> <small>(%s)</small></a></aside>',
			htmlspecialchars($ext), htmlspecialchars($url), htmlspecialchars($caption), htmlspecialchars(strtoupper($ext))
		);
	}

	public function image(bool $block, array $args): string
	{
		static $align_replace = ['gauche' => 'left', 'droite' => 'right', 'centre' => 'center'];

		$name = $args['file'] ?? ($args[0] ?? null);
		$align = $args['align'] ?? ($args[1] ?? null);
		$caption = $args['caption'] ?? (isset($args[2]) ? implode(' ', array_slice($args, 2)) : null);

		$align = strtr((string)$align, $align_replace);

		if (!$name || !$this->renderer->hasPath()) {
			return self::error('Tag image : aucun nom de fichier indiqué.');
		}

		$url = $this->renderer->resolveAttachment($name);
		$size = $align == 'center' ? 500 : 200;
		$svg = substr($name, -4) == '.svg';
		$thumb_url = null;

		if (!$svg) {
			$thumb_url = sprintf('%s?%spx', $url, $size);
		}

		$out = sprintf('<a href="%s" class="internal-image" target="_image"><img src="%s" alt="%s" loading="lazy" /></a>',
			htmlspecialchars($url),
			htmlspecialchars($thumb_url ?? $url),
			htmlspecialchars($caption ?? '')
		);

		if (!empty($align)) {
			if ($caption) {
				$caption = sprintf('<figcaption>%s</figcaption>', htmlspecialchars($caption));
			}

			$out = sprintf('<figure class="image img-%s">%s%s</figure>', $align, $out, $caption);
		}

		return $out;
	}

	/**
	 * <<color|red>>text...<</color>>
	 * <<color|red|blue>>text...<</color>>
	 */
	public function color(bool $block, array $args, ?string $content, string $name): string
	{
		// Only allow color names / hex codes
		foreach ($args as $k => $v) {
			if (!ctype_alnum(str_replace('#', '', strtolower($v)))) {
				unset($args[$k]);
			}
		}

		if (!isset($args[0])) {
			return '';
		}

		$tag = $block ? 'div' : 'span';
		$style = !$block ? 'display: inline; ' : '';
		$args = array_map('htmlspecialchars', $args);

		if ($name == 'color' && count($args) == 1) {
			$style .= 'color: ' . $args[0];
		}
		elseif ($name == 'color') {
			$style .= sprintf('background-size: 100%%; background: linear-gradient(to right, %s); -webkit-background-clip: text; -webkit-text-fill-color: transparent; -moz-text-fill-color: transparent; -moz-background-clip: text;', implode(', ', $args));
		}
		elseif ($name == 'bgcolor' && count($args) == 1) {
			$style .= 'background-color: ' . $args[0];
		}
		else {
			$style .= sprintf('background-size: 100%%; background: linear-gradient(to right, %s); -webkit-background-clip: initial; -webkit-text-fill-color: initial; -moz-text-fill-color: initial; -moz-background-clip: initial;', implode(', ', $args));
		}

		return sprintf('<%s style="%s">', $tag, $style);
	}

	public function colorClose(bool $block): string
	{
		if ($block) {
			return '</div>';
		}
		else {
			return '</span>';
		}
	}

	protected function _filterStyleAttribute(string $str): ?string
	{
		$str = html_entity_decode($str);
		$str = rawurldecode($str);
		$str = str_replace([' ', "\t", "\n", "\r", "\0"], '', $str);

		if (strstr($str, '/*')) {
			return null;
		}

		if (preg_match('/url\s*\(|expression|script:|\\\\|@import/i', $str)) {
			return null;
		}

		return $str;
	}


	public function gridBlock(array $args): string
	{
		$style = '';

		if (isset($args['column'])) {
			$style .= 'grid-column: ' . htmlspecialchars($args['column']);
		}

		if (isset($args['row'])) {
			$style .= 'grid-row: ' . htmlspecialchars($args['row']);
		}

		if (isset($args['align'])) {
			$style .= 'align-self: ' . htmlspecialchars($args['align']);
		}

		$style = $this->_filterStyleAttribute($style);

		return sprintf('<article class="web-block" style="%s">', $style);
	}

	public function grid(bool $block, array $args, ?string $content, string $name): string
	{
		if (!$block) {
			return '';
		}

		$out = '';

		// Split grid in blocks
		if (!isset($args[0]) && !isset($args['short']) && !isset($args['template'])) {
			if (!$this->in_grid) {
				return '';
			}

			return '</article>' . $this->gridBlock($args);
		}

		if ($this->in_grid) {
			$out .= $this->gridClose($block);
		}

		$class = 'web-grid';
		$style = 'grid-template: ';

		// Automatic template from simple string:
		// !! = 2 columns, #!! = 1 50% column, two 25% columns
		if (isset($args[0]) || isset($args['short'])) {
			$template = $args[0] ?? $args['short'];
			$template = preg_replace('/[^!#]/', '', $template);
			$l = strlen($template);
			$fraction = ceil(100*(1/$l)) / 100;
			$template = str_replace('!', sprintf('minmax(0, %sfr) ', $fraction), $template);
			$template = preg_replace_callback('/(#+)/', fn ($match) => sprintf('minmax(0, %sfr) ', $fraction * strlen($match[1])), $template);
			$style .= 'none / ' . trim($template);
		}
		elseif (isset($args['template'])) {
			$style .= $args['template'];
		}
		else {
			$style .= '1fr';
		}

		if (isset($args['gap'])) {
			$style .= '; gap: ' . $args['gap'];
		}

		if (array_key_exists('debug', $args)) {
			$class .= ' web-grid-debug';
		}

		$style = $this->_filterStyleAttribute($style);

		$out .= sprintf('<section class="%s" style="--%s">', $class, htmlspecialchars($style));
		$out .= $this->gridBlock($args);
		$this->in_grid = true;

		return $out;
	}

	public function gridClose(bool $block): string
	{
		$out = '';

		$out .= '</article>';
		$out .= '</section>';

		$this->in_grid = false;
		return $out;
	}

	public function align(bool $block, array $args, ?string $content, string $name): string
	{
		if (!$block) {
			return '';
		}

		return sprintf('<div style="text-align: %s">', $name);
	}

	public function alignClose(bool $block): string
	{
		if (!$block) {
			return '';
		}

		return '</div>';
	}
}
