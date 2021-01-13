<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

use Garradin\Squelette_Filtres;
use Garradin\Plugin;
use Garradin\Utils;
use Garradin\Files\Files;

use KD2\SkrivLite;

use const Garradin\WWW_URL;

class Skriv
{
	static protected $skriv;

	static public function render(?File $file, string $str, array $options = []): string
	{
		if (!isset($options['prefix'])) {
			$options['prefix'] = WWW_URL;
		}

		if (!self::$skriv)
		{
			self::$skriv = new \KD2\SkrivLite;
			self::$skriv->registerExtension('fichier', [self::class, 'SkrivFichier']);
			self::$skriv->registerExtension('image', [self::class, 'SkrivImage']);

			// Enregistrer d'autres extensions éventuellement
			Plugin::fireSignal('skriv.init', ['skriv' => self::$skriv]);
		}

		$skriv =& self::$skriv;

		$str = preg_replace_callback('/(fichier|image):\/\/(\d+)/', function ($match) use ($skriv) {
			$file = Files::get((int)$match[2]);

			if (!$file) {
				return $skriv->parseError('/!\ Lien fichier invalide');
			}

			return $file->url();
		}, $str);

		$str = self::$skriv->render($str);

		$str = Squelette_Filtres::typo_fr($str);

		$str = preg_replace_callback('!<a href="([^/.:@]+)">!i', function ($matches) use ($options) {
			return sprintf('<a href="%s%s">', $options['prefix'], Utils::transformTitleToURI($matches[1]));
		}, $str);

		return $str;
	}

	/**
	 * Callback utilisé pour l'extension <<fichier>> dans le wiki-texte
	 * @param array $args    Arguments passés à l'extension
	 * @param string $content Contenu éventuel (en mode bloc)
	 * @param object $skriv   Objet SkrivLite
	 */
	static public function SkrivFichier(array $args, ?string $content, SkrivLite $skriv): string
	{
		$id = $caption = null;

		foreach ($args as $value)
		{
			if (preg_match('/^\d+$/', $value) && !$id)
			{
				$id = (int)$value;
				break;
			}
			else
			{
				$caption = trim($value);
			}
		}

		if (empty($id))
		{
			return $skriv->parseError('/!\ Tag fichier : aucun numéro de fichier indiqué.');
		}

		$file = Files::get((int)$id);

		if (!$file) {
			return $skriv->parseError('/!\ Tag fichier invalide');
		}

		if (empty($caption))
		{
			$caption = $file->name;
		}

		$out = '<aside class="fichier" data-type="'.$skriv->escape($file->type).'">';
		$out.= '<a href="'.$file->getURL().'" class="internal-file">'.$skriv->escape($caption).'</a> ';
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
		static $align_values = ['droite', 'gauche', 'centre'];

		$align = '';
		$id = $caption = null;

		foreach ($args as $value)
		{
			if (preg_match('/^\d+$/', $value) && !$id)
			{
				$id = (int)$value;
			}
			else if (in_array($value, $align_values) && !$align)
			{
				$align = $value;
			}
			else
			{
				$caption = $value;
			}
		}

		if (!$id)
		{
			return $skriv->parseError('/!\ Tag image : aucun numéro de fichier indiqué.');
		}

		$file = Files::get((int)$id);

		if (!$file) {
			return $skriv->parseError('/!\ Tag image invalide');
		}

		if (!$file->image)
		{
			return $skriv->parseError('/!\ Tag image : ce fichier n\'est pas une image.');
		}

		$out = '<a href="'.$file->url().'" class="internal-image">';
		$out .= '<img src="'.$file->url($align == 'centre' ? 500 : 200).'" alt="';

		if ($caption)
		{
			$out .= htmlspecialchars($caption);
		}

		$out .= '" /></a>';

		if (!empty($align))
		{
			$out = '<figure class="image ' . $align . '">' . $out;

			if ($caption)
			{
				$out .= '<figcaption>' . htmlspecialchars($caption) . '</figcaption>';
			}

			$out .= '</figure>';
		}

		return $out;
	}
}
