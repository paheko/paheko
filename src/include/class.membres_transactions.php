<?php

namespace Garradin;

class Membres_Transactions
{
	const ITEMS_PER_PAGE = 100;

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

		$db->exec('BEGIN;');

		$db->simpleInsert('membres_transactions', [
			'date'				=>	$data['date'],
			'id_transaction'	=>	$data['id_transaction'],
			'montant'			=>	$data['montant'],
			'libelle'			=>	$data['libelle'],
			'id_membre'			=>	$data['id_membre'],
			]);

		$id = $db->lastInsertRowId();

		$id_cat = $db->simpleQuerySingle('SELECT id_categorie_compta FROM transactions WHERE id = ?;', 
			false, (int)$data['id_transaction']);

		if ($id_cat)
		{
			try {
		        $this->addOperationCompta($id, [
		        	'id_categorie'	=>	$id_cat,
		            'libelle'       =>  $data['libelle'],
		            'montant'       =>  $data['montant'],
		            'date'          =>  $data['date'],
		            'moyen_paiement'=>  $data['moyen_paiement'],
		            'numero_cheque' =>  $data['numero_cheque'],
		            'id_auteur'     =>  $data['id_auteur'],
		            'moyen_paiement'=>	$data['moyen_paiement'],
		            'numero_cheque'	=>	$data['numero_cheque'],
		            'banque'		=>	$data['banque'],
		        ]);
	        }
	        catch (\Exception $e)
	        {
	        	$db->exec('ROLLBACK;');
	        	throw $e;
	        }
		}

		$db->exec('END;');

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
	public function listOperationsCompta($id)
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT * FROM compta_journal
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
		$journal = new Compta_Journal;
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

        if (!isset($data['moyen_paiement']) || trim($data['moyen_paiement']) === '')
        {
        	throw new UserException('Moyen de paiement inconnu ou invalide.');
        }

		if ($data['moyen_paiement'] != 'ES')
        {
            if (trim($data['banque']) == '')
            {
                throw new UserException('Le compte bancaire choisi est invalide.');
            }

            if (!$db->simpleQuerySingle('SELECT 1 FROM compta_comptes_bancaires WHERE id = ?;',
            	false, $data['banque']))
            {
                throw new UserException('Le compte bancaire choisi n\'existe pas.');
            }

            $debit = $data['banque'];
        }
        else
        {
        	$debit = Compta_Comptes::CAISSE;
        }

        $credit = $db->simpleQuerySingle('SELECT compte FROM compta_categories WHERE id = ?;', 
        	false, $data['id_categorie']);

        $id_operation = $journal->add([
            'libelle'       =>  $data['libelle'],
            'montant'       =>  $data['montant'],
            'date'          =>  $data['date'],
            'moyen_paiement'=>  $data['moyen_paiement'],
            'numero_cheque' =>  isset($data['numero_cheque']) ? $data['numero_cheque'] : null,
            'compte_debit'  =>  $debit,
            'compte_credit' =>  $credit,
            'id_categorie'  =>  (int)$data['id_categorie'],
            'id_auteur'     =>  (int)$data['id_auteur'],
        ]);

        $db->simpleInsert('membres_transactions_operations', [
        	'id_operation' => $id_operation,
        	'id_membre_transaction' => $id,
        ]);

        return $id_operation;
	}

	/**
	 * Renvoie un paiement membre
	 * @param  integer $id Numéro du paiement
	 * @return array Données du paiement
	 */
	public function get($id)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT *,
			(SELECT COUNT(*) FROM membres_transactions_operations WHERE id_membre_transaction = id) AS nb_operations
			FROM membres_transactions WHERE id = ?;', true, (int) $id);
	}

	/**
	 * Liste des paiements pour une activité/cotisation
	 * @param  integer  $id   Numéro de transaction
	 * @param  integer $page Numéro de page pour la pagination
	 * @return array        Liste des activités
	 */
	public function listForTransaction($id, $page = 1)
	{
		$begin = ($page - 1) * self::ITEMS_PER_PAGE;

		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT * FROM membres_transactions 
			WHERE id_transaction = ? ORDER BY date DESC LIMIT ?,?;',
			\SQLITE3_ASSOC, (int)$id, $begin, self::ITEMS_PER_PAGE);
	}

	/**
	 * Nombre de paiements pour une activité
	 * @param  integer $id Numéro de l'activité/cotisation
	 * @return integer     Nombre de paiements pour cette activité
	 */
	public function countForTransaction($id)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT COUNT(*) FROM membres_transactions 
			WHERE id_transaction = ?;',
			false, (int)$id);
	}

	/**
	 * Nombre de membres pour une activité
	 * @param  integer $id Numéro de l'activité/cotisation
	 * @return integer     Nombre de paiements pour cette activité
	 */
	public function countMembersForTransaction($id)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT COUNT(DISTINCT id_membre) FROM membres_transactions 
			WHERE id_transaction = ?;',
			false, (int)$id);
	}

	/**
	 * Liste des membres qui ont payé une activité
	 * @param  integer $id Numéro de l'activité
	 * @return array     Liste des membres ayant un paiement associé
	 */
	public function listMembersForTransaction($id, $page = 1)
	{
		$begin = ($page - 1) * self::ITEMS_PER_PAGE;

		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT SUM(mtr.montant) AS total, mtr.id_membre,
			(SELECT nom FROM membres WHERE id = mtr.id_membre) AS nom, tr.montant,
			tr.montant - SUM(mtr.montant) AS a_payer, 
			CASE WHEN tr.duree IS NOT NULL THEN date(mtr.date, \'+\'||tr.duree||\' days\') >= date()
			WHEN tr.fin IS NOT NULL THEN tr.fin <= date() ELSE 1 END AS a_jour
			FROM membres_transactions AS mtr
				INNER JOIN transactions AS tr ON tr.id = mtr.id_transaction
			WHERE
				mtr.id_transaction = ?
			GROUP BY id_membre ORDER BY date DESC LIMIT ?,?;',
			\SQLITE3_ASSOC, (int)$id, $begin, self::ITEMS_PER_PAGE);
	}

	public function listForMember($id)
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT mtr.*, tr.intitule, tr.duree, tr.debut, tr.fin,
				(SELECT COUNT(*) FROM membres_transactions_operations WHERE id_membre_transaction = mtr.id) AS nb_operations
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
		return $db->simpleQuerySingle('SELECT
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