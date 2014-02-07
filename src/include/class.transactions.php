<?php

namespace Garradin;

class Transactions
{
	/**
	 * Vérification des champs fournis pour la modification de donnée
	 * @param  array $data Tableau contenant les champs à ajouter/modifier
	 * @return void
	 */
	protected function _checkFields(&$data)
	{
		$db = DB::getInstance();

		if (!isset($data['intitule']) || trim($data['intitule']) == '')
		{
			throw new UserException('L\'intitulé ne peut rester vide.');
		}

		$data['intitule'] = trim($data['intitule']);

		if (isset($data['description']))
		{
			$data['description'] = trim($data['description']);
		}

		if (!isset($data['montant']) || !is_numeric($data['montant']) || (float)$data['montant'] < 0)
		{
			throw new UserException('Le montant doit être un nombre positif et valide.');
		}

		$data['montant'] = (float) $data['montant'];

		if (isset($data['duree']))
		{
			$data['duree'] = (int) $data['duree'];

			if ($data['duree'] < 0)
			{
				$data['duree'] = 0;
			}
		}

		if (isset($data['debut']) && trim($data['debut']) != '')
		{
			if (!empty($data['duree']))
			{
				throw new UserException('Il n\'est pas possible de spécifier une durée ET une date fixe, merci de choisir l\'une des deux options.');
			}

			if (!isset($data['fin']) || trim($data['fin']) == '')
			{
				throw new UserException('Une date de fin est obligatoire avec la date de début de validité.');
			}

			if (!utils::checkDate($data['debut']))
			{
				throw new UserException('La date de début est invalide.');
			}

			if (!utils::checkDate($data['fin']))
			{
				throw new UserException('La date de fin est invalide.');
			}
		}

		if (isset($data['id_categorie_compta']))
		{
			if ($data['id_categorie_compta'] != 0 && !$db->simpleQuerySingle('SELECT 1 FROM compta_categories WHERE id = ?;', false, (int) $data['id_categorie_compta']))
			{
				throw new UserException('Catégorie comptable inconnue');
			}

			$data['id_categorie_compta'] = (int) $data['id_categorie_compta'];
		}
	}

	/**
	 * Ajouter une transaction
	 * @param array $data Tableau des champs à insérer
	 * @return integer ID de la transaction créée
	 */
	public function add($data)
	{
		$db = DB::getInstance();

		$this->_checkFields($data);

		$db->simpleInsert('transactions', $data);
		$id = $db->lastInsertRowId();

		return $id;
	}

	/**
	 * Modifier une transaction
	 * @param  integer $id  ID de la transaction à modifier
	 * @param  array $data Tableau des champs à modifier
	 * @return bool true si succès
	 */
	public function edit($id, $data)
	{
		$db = DB::getInstance();

        $this->_checkFields($data);

        return $db->simpleUpdate('transactions', $data, 'id = \''.(int) $id.'\'');
	}

	/**
	 * Supprimer une transaction
	 * @param  integer $id ID de la transaction à supprimer
	 * @return integer true en cas de succès
	 */
	public function delete($id)
	{
		$db = DB::getInstance();

		// On n'accepte pas de supprimer une transaction qui est utilisée
		if ($db->simpleQuerySingle('SELECT 1 FROM membres_transactions WHERE id_transaction = ? LIMIT 1;', false, (int) $id))
		{
			throw new UserException('Il existe des paiements utilisant cette activité ou cotisation.');
		}

		// Remise à zéro des écritures faisant référence à cette transaction
		// (s'il y en a encore)
		$db->simpleExec('UPDATE compta_journal SET id_transaction = NULL 
			WHERE id_transaction = ?;', (int) $id);

		return $db->simpleExec('DELETE FROM transactions WHERE id = ?;', (int) $id);
	}

	public function get($id)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT *,
			(SELECT COUNT(*) FROM membres_transactions WHERE id_transaction = :id) AS nb_paiements,
			(SELECT COUNT(DISTINCT id_membre) FROM membres_transactions WHERE id_transaction = :id) AS nb_membres
			FROM transactions WHERE id = :id;', true, ['id' => (int) $id]);
	}

	public function listByName()
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT * FROM transactions ORDER BY intitule;');
	}

	public function listCurrent()
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT * FROM transactions WHERE fin >= date(\'now\') OR fin IS NULL ORDER BY intitule;');
	}

	public function listCurrentWithStats()
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT *,
			(SELECT COUNT(*) FROM membres_transactions WHERE id_transaction = transactions.id) AS nb_paiements,
			(SELECT COUNT(DISTINCT id_membre) FROM membres_transactions WHERE id_transaction = transactions.id) AS nb_membres
			FROM transactions WHERE fin >= date(\'now\') OR fin IS NULL ORDER BY intitule;');
	}
}

?>