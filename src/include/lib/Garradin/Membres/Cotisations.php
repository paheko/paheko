<?php

namespace Garradin\Membres;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Cotisations
{
	const ITEMS_PER_PAGE = 100;

	/**
	 * Vérification des champs fournis pour la modification de donnée
	 * @param  array $data Tableau contenant les champs à ajouter/modifier
	 * @return void
	 */
	protected function _checkFields(&$data, $compta = false)
	{
		$db = DB::getInstance();

        if (empty($data['date']) || !Utils::checkDate($data['date']))
        {
            throw new UserException('Date vide ou invalide.');
        }

		if (empty($data['id_cotisation']) 
			|| !$db->simpleQuerySingle('SELECT 1 FROM cotisations WHERE id = ?;', false, (int) $data['id_cotisation']))
		{
			throw new UserException('Cotisation inconnue.');
		}

		$data['id_cotisation'] = (int) $data['id_cotisation'];

		if (empty($data['id_membre']) 
			|| !$db->simpleQuerySingle('SELECT 1 FROM membres WHERE id = ?;', false, (int) $data['id_membre']))
		{
			throw new UserException('Membre inconnu ou invalide.');
		}

		$data['id_membre'] = (int) $data['id_membre'];

		if ($compta)
		{
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
	        }

	        if (!isset($data['montant']) || !is_numeric($data['montant']) || $data['montant'] < 0)
	        {
	        	throw new UserException('Le montant indiqué n\'est pas un nombre valide : doit être supérieur ou égal à zéro.');
	        }
	    }
	}

	/**
	 * Enregistrer un événement de cotisation
	 * @param array $data Tableau des champs à insérer
	 * @return integer ID de l'événement créé
	 */
	public function add($data)
	{
		$db = DB::getInstance();

		$co = $db->simpleQuerySingle('SELECT * FROM cotisations WHERE id = ?;', 
			true, (int)$data['id_cotisation']);

		$this->_checkFields($data, !empty($co['id_categorie_compta']));

		$check = $db->simpleQuerySingle('SELECT 1 FROM cotisations_membres 
			WHERE id_cotisation = ? AND id_membre = ? AND date = ?;', 
			false, (int)$data['id_cotisation'], (int)$data['id_membre'], $data['date']);

		if ($check)
		{
			throw new UserException('Cette cotisation a déjà été enregistrée pour ce jour-ci et ce membre-ci.');
		}

		$db->begin();

		$db->simpleInsert('cotisations_membres', [
			'date'				=>	$data['date'],
			'id_cotisation'		=>	$data['id_cotisation'],
			'id_membre'			=>	$data['id_membre'],
			]);

		$id = $db->lastInsertRowId();

		if ($co['id_categorie_compta'] && $data['montant'] > 0)
		{
			try {
		        $id_operation = $this->addOperationCompta($id, [
		        	'id_categorie'	=>	$co['id_categorie_compta'],
		            'libelle'       =>  'Cotisation (automatique)',
		            'montant'       =>  $data['montant'],
		            'date'          =>  $data['date'],
		            'moyen_paiement'=>  $data['moyen_paiement'],
		            'numero_cheque' =>  isset($data['numero_cheque']) ? $data['numero_cheque'] : null,
		            'id_auteur'     =>  $data['id_auteur'],
		            'banque'		=>	isset($data['banque']) ? $data['banque'] : null,
		            'id_membre'		=>	$data['id_membre'],
		        ]);
	        }
	        catch (\Exception $e)
	        {
	        	$db->rollback();
	        	throw $e;
	        }
		}

		$db->commit();

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
		$db->simpleExec('UPDATE membres_operations SET id_cotisation = NULL WHERE id_cotisation = ?;', (int)$id);
		return $db->simpleExec('DELETE FROM cotisations_membres WHERE id = ?;', (int) $id);
	}

	public function get($id)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT * FROM cotisations_membres WHERE id = ?;', true, (int)$id);
	}

	/**
	 * Renvoie une liste des écritures comptables liées à une cotisation
	 * @param  int $id Numéro de la cotisation membre
	 * @return array Liste des écritures
	 */
	public function listOperationsCompta($id)
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT * FROM compta_journal
			WHERE id IN (SELECT id_operation FROM membres_operations
				WHERE id_cotisation = ?);', \SQLITE3_ASSOC, (int)$id);
	}

	/**
	 * Compte les opérations comptables liées à cette cotisation
	 */
	public function countOperationsCompta($id)
	{
		return DB::getInstance()->simpleQuerySingle('SELECT COUNT(*) FROM membres_operations WHERE id_cotisation = ?;', false, (int)$id);
	}

	/**
	 * Ajouter une écriture comptable pour un paiemement membre
	 * @param int $id Numéro de la cotisation membre
	 * @param array $data Données
	 */
	public function addOperationCompta($id, $data)
	{
		$journal = new \Garradin\Compta\Journal;
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

		if ($data['moyen_paiement'] != 'ES')
		{
            $debit = $data['banque'];
        }
        else
        {
        	$debit = \Garradin\Compta\Comptes::CAISSE;
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

        $db->simpleInsert('membres_operations', [
        	'id_operation' => $id_operation,
        	'id_membre' => $data['id_membre'],
        	'id_cotisation' => (int)$id,
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
		return $db->simpleQuerySingle('SELECT COUNT(DISTINCT cm.id_membre)
			FROM cotisations_membres  AS cm
				INNER JOIN membres AS m ON m.id = cm.id_membre
			WHERE cm.id_cotisation = ?
			AND m.id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1);',
			false, (int)$id);
	}

	/**
	 * Liste des membres qui sont inscrits à une cotisation
	 * @param  integer $id Numéro de la cotisation
	 * @return array     Liste des membres
	 */
	public function listMembersForCotisation($id, $page = 1, $order = null, $desc = true)
	{
		$begin = ($page - 1) * self::ITEMS_PER_PAGE;

		$db = DB::getInstance();
		$champ_id = Config::getInstance()->get('champ_identite');

		if (empty($order))
			$order = 'date';

		switch ($order)
		{
			case 'date':
			case 'a_jour':
				break;
			case 'identite':
				$order = 'transliterate_to_ascii('.$champ_id.') COLLATE NOCASE';
				break;
			default:
				$order = 'cm.id_membre';
				break;
		}

		$desc = $desc ? 'DESC' : 'ASC';

		return $db->simpleStatementFetch('SELECT cm.id_membre, cm.date, cm.id,
			m.'.$champ_id.' AS nom, c.montant,
			CASE WHEN c.duree IS NOT NULL THEN date(cm.date, \'+\'||c.duree||\' days\') >= date()
			WHEN c.fin IS NOT NULL THEN c.fin >= date() ELSE 1 END AS a_jour
			FROM cotisations_membres AS cm
				INNER JOIN cotisations AS c ON c.id = cm.id_cotisation
				INNER JOIN membres AS m ON m.id = cm.id_membre
			WHERE
				cm.id_cotisation = ?
				AND m.id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)
			GROUP BY cm.id_membre ORDER BY '.$order.' '.$desc.' LIMIT ?,?;',
			\SQLITE3_ASSOC, (int)$id, $begin, self::ITEMS_PER_PAGE);
	}

	/**
	 * Liste des événements d'un membre
	 * @param  integer $id Numéro de membre
	 * @return array     Liste des événements de cotisation fait par ce membre
	 */
	public function listForMember($id)
	{
		// TODO: récupérer ici le solde payé pour une cotisation, pour savoir si tout a été payé
		// (pour gérer par exemple les paiements effectués en plusieurs versements)
		// mais pour le moment le fonctionnement de compta_journal est trop compliqué pour arriver
		// à récupérer un solde dans une requête simple, la requête serait trop lourde donc on laisse tomber
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT cm.*, c.intitule, c.duree, c.debut, c.fin, c.montant,
			(SELECT COUNT(*) FROM membres_operations WHERE id_cotisation = cm.id) AS nb_operations
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
			WHEN c.fin IS NOT NULL THEN (cm.id IS NOT NULL AND c.fin >= date())
			WHEN cm.id IS NOT NULL THEN 1 ELSE 0 END AS a_jour,
			CASE WHEN c.duree IS NOT NULL THEN date(cm.date, \'+\'||c.duree||\' days\')
			WHEN c.fin IS NOT NULL THEN c.fin ELSE 1 END AS expiration,
			(julianday(date()) - julianday(CASE WHEN c.duree IS NOT NULL THEN date(cm.date, \'+\'||c.duree||\' days\')
			WHEN c.fin IS NOT NULL THEN c.fin END)) AS nb_jours
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
			WHEN c.fin IS NOT NULL THEN (cm.id IS NOT NULL AND c.fin >= date())
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