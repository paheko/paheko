<?php

namespace Garradin;

use KD2\Graphics\Image;
use Garradin\Membres\Session;

class Fichiers
{

	/**
	 * Vérifie si le hash fourni n'est pas déjà stocké
	 * Utile pour par exemple reconnaître un ficher dont le contenu est déjà stocké, et éviter un nouvel upload
	 * @param  string $hash Hash SHA1
	 * @return boolean      TRUE si le hash est déjà présent dans fichiers_contenu, FALSE sinon
	 */
	static public function checkHash($hash)
	{
		return (boolean) DB::getInstance()->firstColumn(
			'SELECT 1 FROM fichiers_contenu WHERE hash = ?;', 
			trim(strtolower($hash))
		);
	}

	/**
	 * Retourne un tableau de hash trouvés dans la DB parmi une liste de hash fournis
	 * @param  array  $list Liste de hash à vérifier
	 * @return array        Liste des hash trouvés
	 */
	static public function checkHashList($list)
	{
		$db = DB::getInstance();

		array_walk($list, function (&$a) use ($db) {
			$a = $db->quote($a);
		});

		$query = sprintf('SELECT hash, 1 FROM fichiers_contenu WHERE hash IN (%s);', 
			implode(', ', $list));
		return $db->getAssoc($query);
	}


	/**
	 * Envoie un fichier déjà stocké
	 * 
	 * @param  string $name Nom du fichier
	 * @param  string $hash Hash SHA1 du contenu du fichier
	 * @return object       Un objet Fichiers en cas de succès
	 */
	static public function uploadExistingHash($name, $hash)
	{
		$db = DB::getInstance();
		$name = preg_replace('/[^\d\w._-]/ui', '', $name);

		$file = $db->first('SELECT * FROM fichiers 
			INNER JOIN fichiers_contenu AS fc ON fc.id = fichiers.id_contenu AND fc.hash = ?;', trim($hash));

		if (!$file)
		{
			throw new UserException('Le fichier à copier n\'existe pas (aucun hash ne correspond à '.$hash.').');
		}

		$db->insert('fichiers', [
			'id_contenu' =>	(int)$file->id_contenu,
			'nom'        =>	$name,
			'type'       =>	$file->type,
			'image'      =>	(int)$file->image,
		]);

		return new Fichiers($db->lastInsertRowID());
	}



	/**
	 * Enlève d'une liste de fichiers ceux qui sont mentionnés dans un texte wiki
	 * @param  array $files Liste de fichiers
	 * @param  string $text  texte wiki
	 * @return array        Un tableau qui ne contient pas les fichiers mentionnés dans $text
	 */
	static public function filterFilesUsedInText($files, $text)
	{
		$used = self::listFilesUsedInText($text);

		return array_filter($files, function ($row) use ($used) {
			return !in_array($row->id, $used);
		});
	}

	/**
	 * Renvoie une liste d'ID de fichiers mentionnées dans un texte wiki
	 * @param  string $text Texte wiki
	 * @return array       Liste des IDs de fichiers mentionnés
	 */
	static public function listFilesUsedInText($text)
	{
		preg_match_all('/<<?(?:fichier|image)\s*(?:\|\s*)?(\d+)/', $text, $match, PREG_PATTERN_ORDER);
		preg_match_all('/(?:fichier|image):\/\/(\d+)/', $text, $match2, PREG_PATTERN_ORDER);

		return array_merge($match[1], $match2[1]);
	}

	/**
	 * Callback utilisé pour l'extension <<fichier>> dans le wiki-texte
	 * @param array $args    Arguments passés à l'extension
	 * @param string $content Contenu éventuel (en mode bloc)
	 * @param object $skriv   Objet SkrivLite
	 */
	static public function SkrivFichier($args, $content, $skriv)
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

		try {
			$file = new Fichiers($id);
		}
		catch (\InvalidArgumentException $e)
		{
			return $skriv->parseError('/!\ Tag fichier : ' . $e->getMessage());
		}

		if (empty($caption))
		{
			$caption = $file->nom;
		}

		$out = '<aside class="fichier" data-type="'.$skriv->escape($file->type).'">';
		$out.= '<a href="'.$file->getURL().'" class="internal-file">'.$skriv->escape($caption).'</a> ';
		$out.= '<small>('.$skriv->escape(($file->type ? $file->type . ', ' : '') . Utils::format_bytes($file->taille)).')</small>';
		$out.= '</aside>';
		return $out;
	}

	/**
	 * Callback utilisé pour l'extension <<image>> dans le wiki-texte
	 * @param array $args    Arguments passés à l'extension
	 * @param string $content Contenu éventuel (en mode bloc)
	 * @param object $skriv   Objet SkrivLite
	 */
	static public function SkrivImage($args, $content, $skriv)
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

		try {
			$file = new Fichiers($id);
		}
		catch (\InvalidArgumentException $e)
		{
			return $skriv->parseError('/!\ Tag image : ' . $e->getMessage());
		}

		if (!$file->image)
		{
			return $skriv->parseError('/!\ Tag image : ce fichier n\'est pas une image.');
		}

		$out = '<a href="'.$file->getURL().'" class="internal-image">';
		$out .= '<img src="'.$file->getURL($align == 'centre' ? 500 : 200).'" alt="';

		if ($caption)
		{
			$out .= htmlspecialchars($caption, ENT_QUOTES, 'UTF-8');
		}

		$out .= '" /></a>';

		if (!empty($align))
		{
			$out = '<figure class="image ' . $align . '">' . $out;

			if ($caption)
			{
				$out .= '<figcaption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>';
			}

			$out .= '</figure>';
		}

		return $out;
	}
}