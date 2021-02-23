<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

use Garradin\Squelette_Filtres;
use Garradin\Plugin;
use Garradin\Utils;
use Garradin\Files\Files;
use Garradin\UserTemplate\CommonModifiers;

use KD2\SkrivLite;

use const Garradin\WWW_URL;

class Skriv
{
	static protected $skriv;

	static public function render(File $file, ?string $content = null, array $options = []): string
	{
		if (!isset($options['prefix'])) {
			$options['prefix'] = WWW_URL;
		}

		if (!self::$skriv)
		{
			self::$skriv = new \KD2\SkrivLite;
			self::$skriv->registerExtension('file', [self::class, 'SkrivFile']);
			self::$skriv->registerExtension('image', [self::class, 'SkrivImage']);

			// Enregistrer d'autres extensions éventuellement
			Plugin::fireSignal('skriv.init', ['skriv' => self::$skriv]);
		}

		$skriv =& self::$skriv;
		$skriv->_currentPath = $file->path;
		$str = $content ?? $file->fetch();

		$str = preg_replace_callback('/#file:\[([^\]\h]+)\]/', function ($match) use ($skriv) {
			return WWW_URL . $skriv->_currentPath . '/' . $match[1];
		}, $str);

		$str = self::$skriv->render($str);

		$str = CommonModifiers::typo($str);

		$str = preg_replace_callback('!<a href="([^/.:@]+)">!i', function ($matches) use ($options) {
			return sprintf('<a href="%s%s">', $options['prefix'], Utils::transformTitleToURI($matches[1]));
		}, $str);

		return sprintf('<div class="web-content">%s</div>', $str);
	}

	/**
	 * Callback utilisé pour l'extension <<file>> dans le wiki-texte
	 * @param array $args    Arguments passés à l'extension
	 * @param string $content Contenu éventuel (en mode bloc)
	 * @param object $skriv   Objet SkrivLite
	 */
	static public function SkrivFile(array $args, ?string $content, SkrivLite $skriv): string
	{
		$name = $args[0] ?? null;
		$caption = $args[1] ?? null;

		if (!$name || !$skriv->_currentPath)
		{
			return $skriv->parseError('/!\ Tag file : aucun nom de fichier indiqué.');
		}

		if (empty($caption))
		{
			$caption = $name;
		}

		$url = WWW_URL . $skriv->_currentPath . '/' . $name;
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
	 * @param object $skriv   Objet SkrivLite
	 */
	static public function SkrivImage(array $args, ?string $content, SkrivLite $skriv): string
	{
		static $align_values = ['left', 'right', 'center'];

		$name = $args[0] ?? null;
		$align = $args[1] ?? null;
		$caption = $args[2] ?? null;

		if (!$name || !$skriv->_currentPath)
		{
			return $skriv->parseError('/!\ Tag image : aucun nom de fichier indiqué.');
		}

		$url = WWW_URL . $skriv->_currentPath . '/' . $name;
		$thumb_url = sprintf('%s?%dpx', $url, $align == 'center' ? 500 : 200);

		$out = sprintf('<a href="%s" class="internal-image" target="_image"><img src="%s" alt="%s" /></a>',
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
