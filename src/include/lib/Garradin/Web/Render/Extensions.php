<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

use Garradin\Plugins;
use KD2\SkrivLite;

/**
 * Common extensions between Skriv and Markdown
 */
class Extensions
{
	public AbstractRender $renderer;

	static public function error(string $msg, bool $block = false)
	{
		$tag = $block ? 'p' : 'b';
		return '<' . $tag . ' style="color: red; background: yellow; padding: 5px">/!\ ' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</' . $tag . '>';
	}

	public function __construct(AbstractRender $renderer)
	{
		$this->renderer = $renderer;
	}

	public function getList(): array
	{
		$list = [
			'file'     => [$this, 'file'],
			'fichier'  => [$this, 'file'],
			'image'    => [$this, 'image'],
			'gallery'  => [$this, 'gallery'],
			'video'    => [$this, 'video'],
		];

		Plugins::fireSignal('render.extensions.init', ['extensions' => &$list]);

		return $list;
	}

	public function gallery(bool $block, array $args, ?string $content): string
	{
		$type = 'gallery';

		if (isset($args['type'])) {
			$type = $args['type'];
		}
		elseif (isset($args[0])) {
			$type = $args[0];
		}

		if (!in_array($type, ['gallery', 'slideshow'])) {
			$type = 'gallery';
		}

		$out = sprintf('<div class="%s"><div class="images">', $type);
		$index = '';

		if (trim((string)$content) === '') {
			$images = $this->renderer->listImages();
		}
		else {
			$images = explode("\n", $content);
		}

		$i = 1;

		foreach ($images as $line) {
			$line = trim($line);

			if ($line === '') {
				continue;
			}

			$img = strtok($line, '|');
			$label = strtok(false);
			$size = $type == 'slideshow' ? 500 : 200;

			$out .= sprintf('<figure>%s</figure>', $this->img($img, $size, $label ?: null));
		}

		$out .= '</div></div>';
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

	public function video(bool $block, array $args): string
	{
		$name = $args['file'] ?? ($args[0] ?? null);

		if (!$name || !$this->renderer->hasPath()) {
			return self::error('Tag image : aucun nom de fichier indiqué.');
		}

		$poster = $args['poster'] ?? ($args[1] ?? null);
		$subs = $args['subtitles'] ?? ($args[2] ?? null);
		$url = $this->renderer->resolveAttachment($name);

		if ($poster) {
			$poster = $this->renderer->resolveAttachment($poster);
		}

		if ($subs) {
			$subs = $this->renderer->resolveAttachment($subs);
			$subs = sprintf('<track kind="subtitles" default="true" src="%s" />', htmlspecialchars($subs));
		}

		$params = '';

		if (isset($args['width'])) {
			$params .= sprintf(' width="%d"', $args['width']);
		}

		if (isset($args['height'])) {
			$params .= sprintf(' height="%d"', $args['height']);
		}

		return sprintf('<video controls="true" preload="%s" poster="%s" src="%s"%s>%s</video>',
			$poster ? 'metadata' : 'none',
			htmlspecialchars($poster),
			htmlspecialchars($url),
			$params,
			$subs
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

		$size = $align == 'center' ? 500 : 200;
		$out = $this->img($name, $size, $caption);

		if (!empty($align)) {
			if ($caption) {
				$caption = sprintf('<figcaption>%s</figcaption>', htmlspecialchars($caption));
			}

			$out = sprintf('<figure class="image img-%s">%s%s</figure>', $align, $out, $caption);
		}

		return $out;
	}

	protected function img(string $name, ?int $thumb_size = 200, ?string $caption = null): string
	{
		$url = $this->renderer->resolveAttachment($name);
		$svg = substr($name, -4) == '.svg';
		$thumb_url = null;

		if (!$svg) {
			$thumb_url = sprintf('%s?%spx', $url, $thumb_size);
		}

		return sprintf('<a href="%s" class="internal-image" target="_image"><img src="%s" alt="%s" loading="lazy" /></a>',
			htmlspecialchars($url),
			htmlspecialchars($thumb_url ?? $url),
			htmlspecialchars($caption ?? '')
		);
	}
}
