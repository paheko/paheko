<?php

namespace Garradin;

class Rappels_Envoyes
{
	const MEDIA_EMAIL = 1;
	const MEDIA_COURRIER = 2;
	const MEDIA_TELEPHONE = 3;
	const MEDIA_AUTRE = 4;

	/**
	 * Vérification des champs fournis pour la modification de donnée
	 * @param  array $data Tableau contenant les champs à ajouter/modifier
	 * @return void
	 */
	protected function _checkFields(&$data)
	{
		$db = DB::getInstance();

        if (isset($data['id_cotisation']))
        {
        	if (!$db->simpleQuerySingle('SELECT 1 FROM cotisations WHERE id = ?;', false, (int) $data['id_cotisation']))
	        {
	            throw new UserException('Cotisation inconnue.');
	        }

	        $data['id_cotisation'] = (int) $data['id_cotisation'];
	    }

        if (empty($data['id_membre'])
        	|| !$db->simpleQuerySingle('SELECT 1 FROM membres WHERE id = ?;', false, (int) $data['id_membre']))
        {
            throw new UserException('Membre inconnu.');
        }

		$data['id_membre'] = (int) $data['id_membre'];

		if (empty($data['media']) || !is_numeric($data['delai']) 
			|| !in_array((int)$data['media'], [self::MEDIA_EMAIL, self::MEDIA_COURRIER, self::MEDIA_TELEPHONE, self::MEDIA_AUTRE]))
		{
			throw new UserException('Média invalide.');
		}

		$data['media'] = (int) $data['media'];

		if (empty($data['date']) || !utils::checkDate($data['date']))
		{
			throw new UserException('La date indiquée n\'est pas valide.');
		}
	}

	/**
	 * Enregistrer un rappel
	 * @param array $data Données du rappel
	 * @return integer Numéro ID du rappel créé
	 */
	public function add($data)
	{
		$db = DB::getInstance();

		$this->_checkFields($data);

		$db->simpleInsert('rappels_envoyes', $data);

		return $db->lastInsertRowId();
	}

	/**
	 * Supprimer un rappel enregistré
	 * @param  integer $id Numéro du rappel
	 * @return boolean     TRUE en cas de succès
	 */
	public function delete($id)
	{
		$db = DB::getInstance();
		$db->simpleExec('DELETE FROM rappels_envoyes WHERE id = ?;', (int) $id);
		return true;
	}

	/**
	 * Renvoie les données sur un rappel
	 * @param  integer $id Numéro du rappel
	 * @return array     Données du rappel
	 */
	public function get($id)
	{
		return DB::getInstance()->simpleQuerySingle('SELECT * FROM rappels_envoyes WHERE id = ?;', true, (int)$id);
	}

	/**
	 * Liste des rappels envoyés à un membre
	 * @param integer $id Numéro du membre
	 * @return array Liste des rappels
	 */
	public function listForMember($id)
	{
		return DB::getInstance()->simpleStatementFetch('SELECT re.*, r.id_cotisation, r.delai, c.intitule
			FROM rappels_envoyes AS re INNER JOIN rappels AS r ON r.id = re.id_rappel
			INNER JOIN cotisations ON c.id = r.id_cotisation 
			WHERE re.id_membre = ?
			ORDER BY re.date DESC;', \SQLITE3_ASSOC, (int)$id);
	}

	/**
	 * Liste des rappels pour une cotisation donnée
	 * @param  integer $id Numéro du rappel
	 * @return array     Liste des rappels
	 */
	public function listForCotisation($id)
	{
		return DB::getInstance()->simpleStatementFetch('SELECT * FROM rappels_envoyes
			WHERE id_rappel = ? ORDER BY date DESC;', \SQLITE3_ASSOC, (int)$id);
	}
}