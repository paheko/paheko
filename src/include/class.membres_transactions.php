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

		if (!isset($data['montant']) || !is_numeric($data['montant']) || (float)$data['montant'] < 0)
		{
			throw new UserException('Le montant doit être un nombre positif et valide.');
		}

		$data['montant'] = (float) $data['montant'];

        if (empty($data['date']) || !utils::checkDate($data['date']))
        {
            throw new UserException('Date vide ou invalide.');
        }

		if (isset($data['id_transaction']))
		{
			if ($data['id_transaction'] != 0 && !$db->simpleQuerySingle('SELECT 1 FROM transactions WHERE id = ?;', false, (int) $data['id_transaction']))
			{
				throw new UserException('Type de transaction inconnu.');
			}

			$data['id_transaction'] = $data['id_transaction'] ? (int) $data['id_transaction'] : null;
		}

		if (isset($data['id_membre']))
		{
			if (!$db->simpleQuerySingle('SELECT 1 FROM membres WHERE id = ?;', false, (int) $data['id_membre']))
			{
				throw new UserException('Membre inconnu ou invalide.');
			}

			$data['id_membre'] = (int) $data['id_membre'];
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

		if (empty($data['id_membre']))
		{
			throw new UserException('Membre inconnu ou invalide.');
		}

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

		return $db->simpleExec('DELETE FROM membres_transactions WHERE id = ?;', (int) $id);
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
		return $db->simpleStatementFetch('SELECT mtr.*, 
				tr.intitule, tr.duree, tr.debut, tr.fin
			FROM membres_transactions AS mtr 
				LEFT JOIN transactions AS tr ON tr.id = mtr.id_transaction
			WHERE mtr.id_membre = ? ORDER BY mtr.date DESC;', \SQLITE3_ASSOC, (int)$id);
	}

	public function listCurrentSubscriptionsForMember($id)
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT SUM(mtr.montant) AS total, tr.montant,
				tr.montant - SUM(mtr.montant) AS a_payer, tr.intitule, tr.duree, tr.debut, tr.fin,
				CASE WHEN tr.duree IS NOT NULL THEN date(mtr.date, \'+\'||tr.duree||\' days\')
				WHEN tr.fin IS NOT NULL THEN tr.fin ELSE NULL END AS expiration
			FROM membres_transactions AS mtr 
				INNER JOIN transactions AS tr ON tr.id = mtr.id_transaction
			WHERE mtr.id_membre = ? AND (
				(tr.duree IS NOT NULL AND mtr.date >= date(\'now\', \'-\'||tr.duree||\' days\'))
				OR (tr.fin IS NOT NULL AND tr.fin >= date(\'now\'))
				OR (tr.fin IS NULL AND tr.duree IS NULL)
			)
			GROUP BY mtr.id_transaction
			ORDER BY tr.intitule;', \SQLITE3_ASSOC, (int)$id);
	}

	public function isMemberUpToDate($id, $id_transaction)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('
			SELECT
				SUM(mtr.montant) AS total, tr.montant,
				tr.montant - SUM(mtr.montant) AS a_payer, tr.intitule, tr.duree, tr.debut, tr.fin,
				CASE WHEN tr.duree IS NOT NULL THEN date(mtr.date, \'+\'||tr.duree||\' days\')
				WHEN tr.fin IS NOT NULL THEN tr.fin ELSE NULL END AS expiration
			FROM transactions AS tr
				LEFT JOIN membres_transactions AS mtr
				ON (tr.id = mtr.id_transaction AND id_membre = ? 
					AND ((tr.duree IS NOT NULL AND mtr.date >= date(\'now\', \'-\'||tr.duree||\' days\')) OR tr.duree IS NULL))
			WHERE tr.id = ? AND ((tr.fin IS NOT NULL AND tr.fin >= date(\'now\')) OR (tr.fin IS NULL))
			GROUP BY tr.id
			ORDER BY mtr.date DESC LIMIT 1;',
			true, (int)$id, (int)$id_transaction);
	}

	public function countForMember($id)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT COUNT(*) FROM membres_transactions 
			WHERE id_membre = ?;', false, (int)$id);
	}
}