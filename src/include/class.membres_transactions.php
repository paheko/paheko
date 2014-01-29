<?php

namespace Garradin;

class Membres_Transactions
{
	/**
	 * Vérification des champs fournis pour la modification de donnée
	 * @param  array $data Tableau contenant les champs à ajouter/modifier
	 * @return void
	 */
	protected function _checkFields(&$data)
	{
		$db = DB::getInstance();

		if (!isset($data['libelle']) || trim($data['libelle']) == '')
		{
			throw new UserException('Le libellé ne peut rester vide.');
		}

		$data['libelle'] = trim($data['libelle']);

		if (empty($data['montant']) || !is_numeric($data['montant']))
		{
			throw new UserException('Le montant doit être un nombre valide.');
		}

		$data['montant'] = (float) $data['montant'];

		if (isset($data['id_transaction']))
		{
			if ($data['id_transaction'] != 0 && !$db->simpleQuerySingle('SELECT 1 FROM transactions WHERE id = ?;', false, (int) $data['id_transaction']))
			{
				throw new UserException('Type de transaction inconnu.');
			}

			$data['id_transaction'] = (int) $data['id_transaction'];
		}

		if (empty($data['id_membre']) 
			|| !$db->simpleQuerySingle('SELECT 1 FROM membres WHERE id = ?;', 
				false, (int) $data['id_membre']))
		{
			throw new UserException('Membre inconnu ou invalide.');
		}

		$data['id_membre'] = (int) $data['id_membre'];
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

		$db->simpleInsert('membres_transactions', $data);
		$id = $db->lastInsertRowId();

		// FIXME création écriture comptable

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

        return $db->simpleUpdate('membres_transactions', $data, 'id = \''.(int) $id.'\'');
	}

	/**
	 * Supprimer un paiement
	 * @param  integer $id ID de la transaction à supprimer
	 * @return integer true en cas de succès
	 */
	public function delete($id)
	{
		$db = DB::getInstance();

		// Supprimer les liaisons mais pas les écritures comptables
		$db->simpleExec('DELETE FROM membres_transactions_operations WHERE id_membre_transaction = ?;',
			(int)$id);

		return $db->simpleExec('DELETE FROM transactions WHERE id = ?;', (int) $id);
	}

	/**
	 * Renvoie une liste des écritures comptables liées à un paiement
	 * @param  int $id Numéro de la transaction
	 * @return array Liste des écritures
	 */
	public function listComptaOperations($id)
	{
		$db = DB::getInstance();
		return $db->simpleQueryFetch('SELECT * FROM compta_journal
			WHERE id IN (SELECT id_operation FROM membres_transactions_operations 
				WHERE id_membre_transaction = ?);', \SQLITE3_ASSOC, (int)$id);
	}

	/**
	 * Ajouter une écriture comptable pour un paiemement membre
	 * @param int $id Numéro de la transaction
	 * @param array $data Données
	 */
	public function addOperationCompta($id, $data)
	{

	}

	public function get($id)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT * FROM membres_transactions WHERE id = ?;', true, (int) $id);
	}

	public function listForMember($id)
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT * FROM membres_transactions 
			WHERE id_membre = ? ORDER BY date DESC;', \SQLITE3_ASSOC, (int)$id);
	}

}