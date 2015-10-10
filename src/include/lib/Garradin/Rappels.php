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

		if ((trim($data['delai']) === '') || !is_numeric($data['delai']))
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
	 * Renvoie le nombre de rappels automatiques enregistrés
	 * @return integer Nombre de rappels
	 */
	public function countAll()
	{
		return DB::getInstance()->simpleQuerySingle('SELECT COUNT(*) FROM rappels;');
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
			INNER JOIN cotisations AS c ON c.id = r.id_cotisation
			ORDER BY r.id_cotisation, r.delai, r.sujet;');
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
	 * Envoi des rappels automatiques par e-mail
	 * @return boolean TRUE en cas de succès
	 */
	public function sendPending()
	{
		$db = DB::getInstance();
		$config = Config::getInstance();

		// Requête compliquée qui fait tout le boulot
		// la logique est un JOIN des tables rappels, cotisations, cotisations_membres et membres
		// pour récupérer la liste des membres qui doivent recevoir une cotisation
		$query = '
		SELECT 
			*,
			/* Nombre de jours avant ou après expiration */
			(julianday(date()) - julianday(expiration)) AS nb_jours,
			/* Date de mise en œuvre du rappel */
			date(expiration, delai || \' days\') AS date_rappel
		FROM (
			SELECT m.*, r.delai, r.sujet, r.texte, r.id_cotisation, r.id AS id_rappel,
				m.'.$config->get('champ_identite').' AS identite,
				CASE WHEN c.duree IS NOT NULL THEN date(cm.date, \'+\'||c.duree||\' days\')
				WHEN c.fin IS NOT NULL THEN c.fin ELSE 0 END AS expiration
			FROM rappels AS r
				INNER JOIN cotisations AS c ON c.id = r.id_cotisation
				INNER JOIN cotisations_membres AS cm ON cm.id_cotisation = c.id
				INNER JOIN membres AS m ON m.id = cm.id_membre
			WHERE
				/* Inutile de sélectionner les membres sans email */
				m.email IS NOT NULL AND m.email != \'\'
				/* Les cotisations ponctuelles ne comptent pas */
				AND (c.fin IS NOT NULL OR c.duree IS NOT NULL)
				/* Rien nest envoyé aux membres des catégories cachées, logique */
				AND m.id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)
    		/* Grouper par membre, pour n\'envoyer qu\'un seul rappel par membre/cotise */
	    	GROUP BY m.id, r.id_cotisation
			ORDER BY r.delai ASC
		)
		WHERE nb_jours >= delai 
			/* Pour ne pas spammer on n\'envoie pas de rappel antérieur au dernier rappel déjà effectué */
			AND id NOT IN (SELECT id_membre FROM rappels_envoyes AS re 
				WHERE id_cotisation = re.id_cotisation 
				AND re.date >= date(expiration, delai || \' days\')
			)
		ORDER BY nb_jours DESC;';

		$db->exec('BEGIN');
		$st = $db->prepare($query);
		$res = $st->execute();
		$re = new Rappels_Envoyes;

		while ($row = $res->fetchArray(DB::ASSOC))
		{
			$re->sendAuto($row);
		}

		$db->exec('END;');
		return true;
	}
}
