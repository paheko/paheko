<?php

namespace Garradin;

class Cotisations_Membres
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

        if (empty($data['date']) || !utils::checkDate($data['date']))
        {
            throw new UserException('Date vide ou invalide.');
        }

		if (empty($data['id_cotisation']) 
			|| !$db->simpleQuerySingle('SELECT 1 FROM cotisations WHERE id = ?;', false, (int) $data['id_cotisation']))
		{
			throw new UserException('Cotisation inconnue.');
		}

		$data['id_cotisation'] = $data['id_cotisation'] ? (int) $data['id_cotisation'] : null;

		if (empty($data['id_membre']) 
			|| !$db->simpleQuerySingle('SELECT 1 FROM membres WHERE id = ?;', false, (int) $data['id_membre']))
		{
			throw new UserException('Membre inconnu ou invalide.');
		}

		$data['id_membre'] = (int) $data['id_membre'];
	}

	/**
	 * Enregistrer un événement de cotisation
	 * @param array $data Tableau des champs à insérer
	 * @return integer ID de l'événement créé
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

		$db->simpleInsert('cotisations_membres', [
			'date'				=>	$data['date'],
			'id_cotisation'		=>	$data['id_cotisation'],
			'id_membre'			=>	$data['id_membre'],
			]);

		$id = $db->lastInsertRowId();

		$id_cat = $db->simpleQuerySingle('SELECT id_categorie_compta FROM cotisations WHERE id = ?;', 
			false, (int)$data['id_cotisation']);

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
	 * Supprimer un événement de cotisation
	 * @param  integer $id ID de l'événement à supprimer
	 * @return integer true en cas de succès
	 */
	public function delete($id)
	{
		$db = DB::getInstance();

		return $db->simpleExec('DELETE FROM cotisations_membres WHERE id = ?;', (int) $id);
	}

	/**
	 * Renvoie une liste des écritures comptables liées à un paiement
	 * @param  int $id Numéro du paiement
	 * @return array Liste des écritures
	 */
	public function listOperationsCompta($id)
	{
		// FIXME
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT * FROM compta_journal
			WHERE id IN (SELECT id_operation FROM cotisations_paiements_compta
				WHERE id_cotisation = ?);', \SQLITE3_ASSOC, (int)$id);
	}

	/**
	 * Ajouter une écriture comptable pour un paiemement membre
	 * @param int $id Numéro du paiement
	 * @param array $data Données
	 */
	public function addOperationCompta($id, $data)
	{
		// FIXME
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

        $db->simpleInsert('cotisations_paiements_compta', [
        	'id_operation' => $id_operation,
        	'id_paiement' => $id,
        ]);

        return $id_operation;
	}

	/**
	 * Nombre de membres pour une cotisation
	 * @param  integer $id Numéro de la cotisation
	 * @return integer     Nombre d'événements pour cette cotisation
	 */
	public function countMembersForCotisation($id)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT COUNT(DISTINCT id_membre) FROM cotisations_membres 
			WHERE id_cotisation = ?;',
			false, (int)$id);
	}

	/**
	 * Liste des membres qui sont inscrits à une cotisation
	 * @param  integer $id Numéro de la cotisation
	 * @return array     Liste des membres
	 */
	public function listMembersForCotisation($id, $page = 1)
	{
		$begin = ($page - 1) * self::ITEMS_PER_PAGE;

		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT cm.id_membre,
			(SELECT nom FROM membres WHERE id = cm.id_membre) AS nom, c.montant,
			CASE WHEN c.duree IS NOT NULL THEN date(cm.date, \'+\'||c.duree||\' days\') >= date()
			WHEN c.fin IS NOT NULL THEN c.fin >= date() ELSE 1 END AS a_jour
			FROM cotisations_membres AS cm
				INNER JOIN cotisations AS c ON c.id = cm.id_cotisation
			WHERE
				cm.id_cotisation = ?
			GROUP BY cm.id_membre ORDER BY cm.date DESC LIMIT ?,?;',
			\SQLITE3_ASSOC, (int)$id, $begin, self::ITEMS_PER_PAGE);
	}

	/**
	 * Liste des événements d'un membre
	 * @param  integer $id Numéro de membre
	 * @return array     Liste des événements de cotisation fait par ce membre
	 */
	public function listForMember($id)
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT cm.*, c.intitule, c.duree, c.debut, c.fin, c.montant
			FROM cotisations_membres AS cm
				LEFT JOIN cotisations AS c ON c.id = cm.id_cotisation
			WHERE cm.id_membre = ? ORDER BY cm.date DESC;', \SQLITE3_ASSOC, (int)$id);
	}

	/**
	 * Liste des cotisations / activités en cours pour ce membre
	 * @param  integer $id Numéro de membre
	 * @return array     Liste des cotisations en cours de validité
	 */
	public function listSubscriptionsForMember($id)
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT c.*,
			CASE WHEN c.duree IS NOT NULL THEN date(cm.date, \'+\'||c.duree||\' days\') >= date()
			WHEN c.fin IS NOT NULL THEN c.fin >= date()
			WHEN cm.id IS NOT NULL THEN 1 ELSE 0 END AS a_jour,
			CASE WHEN c.duree IS NOT NULL THEN date(cm.date, \'+\'||c.duree||\' days\')
			WHEN c.fin IS NOT NULL THEN c.fin ELSE 1 END AS expiration
			FROM cotisations_membres AS cm
				INNER JOIN cotisations AS c ON c.id = cm.id_cotisation
			WHERE cm.id_membre = ?
			GROUP BY cm.id_cotisation
			ORDER BY cm.date DESC;', \SQLITE3_ASSOC, (int)$id);
	}

	/**
	 * Ce membre est-il à jour sur cette cotisation ?
	 * @param  integer  $id             Numéro de membre
	 * @param  integer  $id_cotisation  Numéro de cotisation
	 * @return array 					Infos sur la cotisation, et champ expiration
	 * (si NULL = cotisation jamais enregistrée, si 1 = cotisation ponctuelle enregistrée, sinon date d'expiration)
	 */
	public function isMemberUpToDate($id, $id_cotisation)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT c.*,
			CASE WHEN c.duree IS NOT NULL THEN date(cm.date, \'+\'||c.duree||\' days\') >= date()
			WHEN c.fin IS NOT NULL THEN c.fin >= date()
			WHEN cm.id IS NOT NULL THEN 1 ELSE 0 END AS a_jour,
			CASE WHEN c.duree IS NOT NULL THEN date(cm.date, \'+\'||c.duree||\' days\')
			WHEN c.fin IS NOT NULL THEN c.fin ELSE 1 END AS expiration
			FROM cotisations AS c 
				LEFT JOIN cotisations_membres AS cm ON cm.id_cotisation = c.id AND cm.id_membre = ?
			WHERE c.id = ? ORDER BY cm.date DESC;',
			true, (int)$id, (int)$id_cotisation);
	}

	public function countForMember($id)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT COUNT(DISTINCT id_cotisation) FROM cotisations_membres 
			WHERE id_membre = ?;', false, (int)$id);
	}
}