<?php

namespace Garradin;

class Rappels
{
	/**
	 * Vérification des champs fournis pour la modification de donnée
	 * @param  array $data Tableau contenant les champs à ajouter/modifier
	 * @return void
	 */
	protected function _checkFields(&$data)
	{
		$db = DB::getInstance();

        if (empty($data['id_cotisation'])
        	|| !$db->simpleQuerySingle('SELECT 1 FROM cotisations WHERE id = ?;', false, (int) $data['id_cotisation']))
        {
            throw new UserException('Cotisation inconnue.');
        }

		$data['id_cotisation'] = (int) $data['id_cotisation'];

		if (empty($data['delai']) || !is_numeric($data['delai']))
		{
			throw new UserException('Délai avant rappel invalide : doit être indiqué en nombre de jours.');
		}

		$data['delai'] = (int) $data['delai'];

		if (!isset($data['sujet']) || trim($data['sujet']) === '')
		{
			throw new UserException('Le sujet du rappel ne peut être vide.');
		}

		$data['sujet'] = trim($data['sujet']);

		if (!isset($data['texte']) || trim($data['texte']) === '')
		{
			throw new UserException('Le contenu du rappel ne peut être vide.');
		}

		$data['texte'] = trim($data['texte']);
	}

	/**
	 * Ajouter un rappel
	 * @param array $data Données du rappel
	 * @return integer Numéro ID du rappel créé
	 */
	public function add($data)
	{
		$db = DB::getInstance();

		$this->_checkFields($data);

		$db->simpleInsert('rappels', $data);

		return $db->lastInsertRowId();
	}

	/**
	 * Modifier un rappel automatique
	 * @param  integer 	$id   Numéro du rappel
	 * @param  array 	$data Données du rappel
	 * @return boolean        TRUE si tout s'est bien passé
	 * @throws UserException  En cas d'erreur dans une donnée à modifier
	 */
	public function edit($id, $data)
	{
		$db = DB::getInstance();

		$this->_checkFields($data);

		return $db->simpleUpdate('rappels', $data, 'id = ' . (int)$id);
	}

	/**
	 * Supprimer un rappel automatique
	 * @param  integer $id Numéro du rappel
	 * @param  boolean $delete_history Effacer aussi l'historique des rappels envoyés
	 * @return boolean     TRUE en cas de succès
	 */
	public function delete($id, $delete_history = false)
	{
		$db = DB::getInstance();

		$db->exec('BEGIN;');

		if ($delete_history)
		{
			$db->simpleExec('DELETE FROM rappels_envoyes WHERE id_rappel = ?;', (int) $id);
		}
		else
		{
			$db->simpleExec('UPDATE rappels_envoyes SET id_rappel = NULL WHERE id_rappel = ?;', (int) $id);
		}

		$db->simpleExec('DELETE FROM rappels WHERE id = ?;', (int) $id);
		$db->exec('END;');

		return true;
	}

	/**
	 * Renvoie les données sur un rappel
	 * @param  integer $id Numéro du rappel
	 * @return array     Données du rappel
	 */
	public function get($id)
	{
		return DB::getInstance()->simpleQuerySingle('SELECT * FROM rappels WHERE id = ?;', true, (int)$id);
	}

	/**
	 * Liste des rappels triés par cotisation
	 * @return array Liste des rappels
	 */
	public function listByCotisation()
	{
		return DB::getInstance()->simpleStatementFetch('SELECT r.*,
			c.intitule, c.montant, c.duree, c.debut, c.fin
			FROM rappels AS r
			INNER JOIN cotisations AS c ON c.id = r.id_cotisation ORDER BY r.id_cotisation, r.delai, r.sujet;');
	}

	/**
	 * Liste des rappels pour une cotisation donnée
	 * @param  integer $id Numéro du rappel
	 * @return array     Liste des rappels
	 */
	public function listForCotisation($id)
	{
		return DB::getInstance()->simpleStatementFetch('SELECT * FROM rappels 
			WHERE id_cotisation = ? ORDER BY delai, sujet;', \SQLITE3_ASSOC, (int)$id);
	}

	/**
	 * Remplacer les tags dans le contenu/sujet du mail
	 * @param  string $content Chaîne à traiter
	 * @param  array  $data    Données supplémentaires à utiliser comme tags (tableau associatif)
	 * @return string          $content dont les tags ont été remplacés par le contenu correct
	 */
	public function replaceTagsInContent($content, $data = null)
	{
		$config = Config::getInstance();
		$tags = [
			'#NOM_ASSO'		=>	$config->get('nom_asso'),
			'#ADRESSE_ASSO'	=>	$config->get('adresse_asso'),
			'#EMAIL_ASSO'	=>	$config->get('email_asso'),
			'#SITE_ASSO'	=>	$config->get('site_asso'),
			'#URL_RACINE'	=>	WWW_URL,
			'#URL_SITE'		=>	WWW_URL,
			'#URL_ADMIN'	=>	WWW_URL . 'admin/',
		];

		if (!empty($data) && is_array($data))
		{
			foreach ($data as $key=>$value)
			{
				$key = '#' . strtoupper($key);
				$tags[$key] = $value;
			}
		}

		return strtr($content, $tags);
	}
}