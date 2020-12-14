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

}