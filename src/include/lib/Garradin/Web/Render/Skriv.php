<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

use Garradin\Squelette_Filtres;
use Garradin\Plugin;
use Garradin\Utils;
use Garradin\Files\Files;
use Garradin\UserTemplate\CommonModifiers;

use KD2\SkrivLite;

use const Garradin\{ADMIN_URL, WWW_URL};

class Skriv
{
	static protected $skriv;

	static protected $current_path;
	static protected $context;
	static protected $link_prefix;
	static protected $link_suffix;

	static protected function resolveAttachment(string $uri) {
		$prefix = self::$current_path;
		$pos = strpos($uri, '/');

		// "Image.jpg"
		if ($pos === false) {
			return WWW_URL . $prefix . '/' . $uri;
		}
		// "bla/Image.jpg" outside of web context
		elseif (self::$context !== File::CONTEXT_WEB && $pos !== 0) {
			return WWW_URL . self::$context . '/' . $uri;
		}
		// "bla/Image.jpg" in web context or absolute link, eg. "/transactions/2442/42.jpg"
		else {
			return WWW_URL . ltrim($uri, '/');
		}
	}

	static public function resolveLink(string $uri) {
		$first = substr($uri, 0, 1);
		if ($first == '/' || $first == '!') {
			return Utils::getLocalURL($uri);
		}

		if (strpos(Utils::basename($uri), '.') === false) {
			$uri .= self::$link_suffix;
		}

		return self::$link_prefix . $uri;
	}

	static public function render(?File $file, ?string $content = null, array $options = []): string
	{
		if (!self::$skriv)
		{
			self::$skriv = new \KD2\SkrivLite;
			self::$skriv->registerExtension('file', [self::class, 'SkrivFile']);
			self::$skriv->registerExtension('image', [self::class, 'SkrivImage']);

			// Enregistrer d'autres extensions éventuellement
			Plugin::fireSignal('skriv.init', ['skriv' => self::$skriv]);
		}

		$skriv =& self::$skriv;

		if ($file) {
			self::$current_path = Utils::dirname($file->path);
			self::$context = strtok(self::$current_path, '/');
			self::$link_suffix = '';

			if (self::$context === File::CONTEXT_WEB) {
				self::$link_prefix = WWW_URL . '/';
				self::$current_path = Utils::basename(Utils::dirname($file->path));
			}
			else {
				self::$link_prefix = $options['prefix'] ?? sprintf(ADMIN_URL . 'common/files/preview.php?p=%s/', self::$context);
				self::$link_suffix = '.skriv';
			}
		}

		$str = $content ?? $file->fetch();

		$str = preg_replace_callback('/#file:\[([^\]\h]+)\]/', function ($match) {
			return self::resolveAttachment($match[1]);
		}, $str);

		$str = self::$skriv->render($str);

		$str = CommonModifiers::typo($str);

		$str = preg_replace_callback(';<a href="((?!https?://|\w+:).+)">;i', function ($matches) {
			return sprintf('<a href="%s" target="_parent">', self::resolveLink($matches[1]));
		}, $str);

		return sprintf('<div class="web-content">%s</div>', $str);
	}

	/**
	 * Callback utilisé pour l'extension <<file>> dans le wiki-texte
	 * @param array $args    Arguments passés à l'extension
	 * @param string $content Contenu éventuel (en mode bloc)
	 * @param SkrivLite $skriv   Objet SkrivLite
	 */
	static public function SkrivFile(array $args, ?string $content, SkrivLite $skriv): string
	{
		$name = $args[0] ?? null;
		$caption = $args[1] ?? null;

		if (!$name || !self::$current_path)
		{
			return $skriv->parseError('/!\ Tag file : aucun nom de fichier indiqué.');
		}

		if (empty($caption))
		{
			$caption = $name;
		}

		$url = self::resolveAttachment($name);
		$ext = substr($name, strrpos($name, '.')+1);

		return sprintf(
			'<aside class="file" data-type="%s"><a href="%s" class="internal-file">%s</a> <small>(%s)</small></aside>',
			htmlspecialchars($ext), htmlspecialchars($url), htmlspecialchars($caption), htmlspecialchars(strtoupper($ext))
		);
	}

	/**
	 * Callback utilisé pour l'extension <<image>> dans le wiki-texte
	 * @param array $args    Arguments passés à l'extension
	 * @param string $content Contenu éventuel (en mode bloc)
	 * @param SkrivLite $skriv   Objet SkrivLite
	 */
	static public function SkrivImage(array $args, ?string $content, SkrivLite $skriv): string
	{
		static $align_values = ['left', 'right', 'center'];

		$name = $args[0] ?? null;
		$align = $args[1] ?? null;
		$caption = $args[2] ?? null;

		if (!$name || !self::$current_path)
		{
			return $skriv->parseError('/!\ Tag image : aucun nom de fichier indiqué.');
		}

		$url = self::resolveAttachment($name);
		$thumb_url = sprintf('%s?%dpx', $url, $align == 'center' ? 500 : 200);

		$out = sprintf('<a href="%s" class="internal-image" target="_image"><img src="%s" alt="%s" loading="lazy" /></a>',
			htmlspecialchars($url),
			htmlspecialchars($thumb_url),
			htmlspecialchars($caption ?? $name)
		);

		if (!empty($align))
		{
			if ($caption) {
				$caption = sprintf('<figcaption>%s</figcaption>', htmlspecialchars($caption));
			}

			$out = sprintf('<figure class="image img-%s">%s%s</figure>', $align, $out, $caption);
		}

		return $out;
	}
}
