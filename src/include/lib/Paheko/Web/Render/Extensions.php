<?php

namespace Paheko\Web\Render;

use Paheko\Entities\Files\File;

use Paheko\Plugins;
use Paheko\Utils;
use KD2\SkrivLite;

use const Paheko\{ADMIN_URL, ROOT};

/**
 * Common extensions between Skriv and Markdown
 */
class Extensions
{
	static public AbstractRender $renderer;

	static public function error(string $msg, bool $block = false)
	{
		$tag = $block ? 'p' : 'b';
		return '<' . $tag . ' style="color: red; background: yellow; padding: 5px">/!\ ' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</' . $tag . '>';
	}

	static public function setRenderer(AbstractRender $renderer)
	{
		self::$renderer = $renderer;
	}

	static public function getList(): array
	{
		$list = [
			'file'     => [self::class, 'file'],
			'fichier'  => [self::class, 'file'],
			'image'    => [self::class, 'image'],
			'gallery'  => [self::class, 'gallery'],
			'video'    => [self::class, 'video'],
			'paheko'   => [self::class, 'paheko'],
		];

		$signal = Plugins::fire('render.extensions.init', false, $list);

		if ($signal) {
			$list = array_merge($list, $signal->getOut());
		}

		return $list;
	}

	static public function paheko(bool $block, array $args, ?string $content): string
	{
		if (!empty($args['doc'])) {
			if (!preg_match('/^[\w_-]+$/i', $args['doc'])) {
				return '[Invalid doc]';
			}

			$path = ROOT . '/www/admin/static/doc/' . $args['doc'] . '.html';

			if (!file_exists($path)) {
				return '[Invalid doc]';
			}

			$body = file_get_contents($path);
			$body = substr($body, strpos($body, 'class="web-content"'));
			$body = substr($body, strpos($body, '>')+1);
			$body = substr($body, 0, strrpos($body, '</div'));

			$replace_raw = preg_split('!\s+!', $args['replace'] ?? '');
			$replace = [];

			foreach ($replace_raw as $r) {
				$replace[strtok($r, '=')] = strtok('');
			}

			$body = preg_replace(';<div\s+class="nav">(?:(?!</div>).)*?</div>;s', '', $body);

			// Replace images
			$body = preg_replace(';src="(?!https?:|/);', 'src="' . ADMIN_URL . 'static/doc/', $body);

			// Replace links
			$body = preg_replace_callback('!href="([a-z_-]+)\.html!',
				function($match) use ($args, $replace) {
					$url = $match[1];
					if (array_key_exists($url, $replace)) {
						$url = $replace[$url];
					}
					else {
						$url = str_replace('_', '-', $url);

						if (isset($args['prefix'])) {
							$url = $args['prefix'] . $url;
						}
					}

					return 'href="' . $url;
				}, $body);
			return $body;
		}
		elseif (isset($args['version']) || in_array('version', $args)) {
			return \Paheko\paheko_version();
		}

		return '';
	}

	static public function gallery(bool $block, array $args, ?string $content): string
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
			$images = self::$renderer->listImagesFilenames();
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
			$size = $type === 'slideshow' ? File::THUMB_SIZE_LARGE : File::THUMB_SIZE_TINY;

			$out .= sprintf('<figure>%s</figure>', self::img($img, $size, $label ?: null));
		}

		$out .= '</div></div>';
		return $out;
	}

	static public function file(bool $block, array $args): string
	{
		$name = $args[0] ?? null;
		$caption = $args[1] ?? null;

		if (!$name || !self::$renderer->hasPath()) {
			return self::error('Tag file : aucun nom de fichier indiqué.');
		}

		if (empty($caption)) {
			$caption = substr($name, 0, strrpos($name, '.'));
		}

		$file = self::$renderer->resolveAttachment($name);

		if (!$file) {
			return self::error('Tag file : nom de fichier introuvable.');
		}

		$ext = $file->extension();
		$thumb = '';

		if ($thumb_url = $file->thumb_url()) {
			$thumb = sprintf('<img src="%s" alt="%s" loading="lazy" />',
				htmlspecialchars($thumb_url),
				htmlspecialchars($file->name)
			);
		}

		return sprintf(
			'<figure class="file" data-type="%s"><a href="%s" class="internal-file">%s<figcaption><b>%s</b> <small>%s — %s</small></figcaption></a></figure>',
			htmlspecialchars($ext),
			htmlspecialchars($file->url()),
			$thumb,
			htmlspecialchars($caption),
			htmlspecialchars($file->getFormatDescription()),
			htmlspecialchars(Utils::format_bytes($file->size))
		);
	}

	static public function video(bool $block, array $args): string
	{
		$name = $args['file'] ?? ($args[0] ?? null);

		if (!$name || !self::$renderer->hasPath()) {
			return self::error('Tag video : aucun nom de fichier indiqué.');
		}

		$poster = $args['poster'] ?? ($args[1] ?? null);
		$subs = $args['subtitles'] ?? ($args[2] ?? null);
		$video = self::$renderer->resolveAttachment($name);

		if (!$video) {
			return self::error('Tag video : nom de fichier introuvable.');
		}

		if ($poster) {
			$poster = self::$renderer->resolveAttachment($poster);
		}

		if ($subs) {
			$subs = self::$renderer->resolveAttachment($subs);
			$subs = sprintf('<track kind="subtitles" default="true" src="%s" />', htmlspecialchars($subs->url()));
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
			htmlspecialchars($poster ? $poster->url() : ''),
			htmlspecialchars($video->url()),
			$params,
			$subs
		);
	}

	static public function image(bool $block, array $args): string
	{
		static $align_replace = ['gauche' => 'left', 'droite' => 'right', 'centre' => 'center'];

		$name = $args['file'] ?? ($args[0] ?? null);
		$align = $args['align'] ?? ($args[1] ?? null);
		$caption = $args['caption'] ?? (isset($args[2]) ? implode(' ', array_slice($args, 2)) : null);

		$align = strtr((string)$align, $align_replace);

		if (!$name || !self::$renderer->hasPath()) {
			return self::error('Tag image : aucun nom de fichier indiqué.');
		}

		$size = $align === 'center' ? File::THUMB_SIZE_LARGE : File::THUMB_SIZE_TINY;
		$out = self::img($name, $align ? $size : null, $caption);

		if (!empty($align)) {
			if ($caption) {
				$caption = sprintf('<figcaption>%s</figcaption>', htmlspecialchars($caption));
			}

			$out = sprintf('<figure class="image img-%s">%s%s</figure>', $align, $out, $caption);
		}

		return $out;
	}

	static protected function img(string $name, ?string $thumb_size = File::THUMB_SIZE_TINY, ?string $caption = null): string
	{
		$file = self::$renderer->resolveAttachment($name);

		if (!$file) {
			return self::error('Tag image : nom de fichier inconnu : ' . $name);
		}

		$svg = substr($name, -4) == '.svg';
		$thumb_url = null;
		$url = $file->url();

		if ($svg || !$thumb_size) {
			$thumb_url = $url;
		}
		else {
			$thumb_url = $file->thumb_url($thumb_size);
		}

		return sprintf('<a href="%s" class="internal-image" target="_image"><img src="%s" alt="%s" loading="lazy" /></a>',
			htmlspecialchars($url),
			htmlspecialchars($thumb_url ?? $url),
			htmlspecialchars($caption ?? '')
		);
	}
}
