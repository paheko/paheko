<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;

use Garradin\Plugin;
use Garradin\UserTemplate\CommonModifiers;

use KD2\SkrivLite;

use const Garradin\{ADMIN_URL, WWW_URL};

class Skriv extends AbstractRender
{
	protected $skriv;

	public function __construct(?File $file = null, ?string $user_prefix = null)
	{
		parent::__construct($file, $user_prefix);

		$this->skriv = new SkrivLite;
		$this->skriv->registerExtension('file', [$this, 'SkrivFile']);
		$this->skriv->registerExtension('fichier', [$this, 'SkrivFile']);
		$this->skriv->registerExtension('image', [$this, 'SkrivImage']);

		// Enregistrer d'autres extensions éventuellement
		Plugin::fireSignal('skriv.init', ['skriv' => $this->skriv]);
	}

	public function render(?string $content = null): string
	{
		$skriv =& $this->skriv;

		$str = $content ?? $this->file->fetch();

		$str = preg_replace_callback('/#file:\[([^\]\h]+)\]/', function ($match) {
			return $this->resolveAttachment($match[1]);
		}, $str);

		$str = $skriv->render($str);

		$str = CommonModifiers::typo($str);

		$str = preg_replace_callback(';<a href="((?!https?://|\w+:).+)">;i', function ($matches) {
			return sprintf('<a href="%s" target="_parent">', $this->resolveLink($matches[1]));
		}, $str);

		return sprintf('<div class="web-content">%s</div>', $str);
	}

	public function callExtension(array $match)
	{
		$method = new \ReflectionMethod($this->skriv, '_callExtension');
		$method->setAccessible(true);
		return $method->invoke($this->skriv, $match);
	}

	/**
	 * Callback utilisé pour l'extension <<file>> dans le wiki-texte
	 * @param array $args    Arguments passés à l'extension
	 * @param string $content Contenu éventuel (en mode bloc)
	 * @param SkrivLite $skriv   Objet SkrivLite
	 */
	public function SkrivFile(array $args, ?string $content, SkrivLite $skriv): string
	{
		$name = $args[0] ?? null;
		$caption = $args[1] ?? null;

		if (!$name || null === $this->current_path)
		{
			return $skriv->parseError('/!\ Tag file : aucun nom de fichier indiqué.');
		}

		if (empty($caption))
		{
			$caption = $name;
		}

		$url = $this->resolveAttachment($name);
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
	public function SkrivImage(array $args, ?string $content, SkrivLite $skriv): string
	{
		static $align_replace = ['gauche' => 'left', 'droite' => 'right', 'centre' => 'center'];

		$name = $args[0] ?? null;
		$align = $args[1] ?? null;
		$caption = $args[2] ?? null;

		$align = strtr($align, $align_replace);

		if (!$name || null === $this->current_path)
		{
			return $skriv->parseError('/!\ Tag image : aucun nom de fichier indiqué.');
		}

		$url = $this->resolveAttachment($name);
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
