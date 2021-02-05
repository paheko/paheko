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
		$skriv->_currentFile = $file;
		$str = $content ?? $file->fetch();

		$str = preg_replace_callback('/#page:\[([^\]\h]+)\]/', function ($match) use ($skriv) {
			$file = Files::get($match[1]);

			if (!$file) {
				return $skriv->parseError('/!\ Lien fichier invalide');
			}

			return $file->url();
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

		if (!$name || !$skriv->_currentFile)
		{
			return $skriv->parseError('/!\ Tag file : aucun nom de fichier indiqué.');
		}

		$file = $skriv->_currentFile->getSubFile($name);

		if (!$file) {
			return $skriv->parseError('/!\ Tag fichier invalide: fichier non trouvé');
		}

		if (empty($caption))
		{
			$caption = $file->name;
		}

		$out = '<aside class="file" data-type="'.$skriv->escape($file->type).'">';
		$out.= '<a href="'.$file->url().'" class="internal-file">'.$skriv->escape($caption).'</a> ';
		$out.= '<small>('.$skriv->escape(($file->type ? $file->type . ', ' : '') . Utils::format_bytes($file->size)).')</small>';
		$out.= '</aside>';
		return $out;
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

		if (!$name)
		{
			return $skriv->parseError('/!\ Tag image : aucun nom de fichier indiqué.');
		}

		$file = $skriv->_currentFile->getSubFile($name);

		if (!$file) {
			return $skriv->parseError('/!\ Tag image invalide: fichier non trouvé');
		}


		if (!$file->image)
		{
			return $skriv->parseError('/!\ Tag image : ce fichier n\'est pas une image.');
		}

		$out = '<a href="'.$file->url.'" class="internal-image">';
		$out .= '<img src="'.$file->thumb_url($align == 'center' ? 500 : 200).'" alt="';

		if ($caption)
		{
			$out .= htmlspecialchars($caption);
		}

		$out .= '" /></a>';

		if (!empty($align))
		{
			$out = '<figure class="image img-' . $align . '">' . $out;

			if ($caption)
			{
				$out .= '<figcaption>' . htmlspecialchars($caption) . '</figcaption>';
			}

			$out .= '</figure>';
		}

		return $out;
	}
}
