<?php

namespace Garradin;

class Cotisations
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
			throw new UserException('Le montant doit être un nombre supérieur ou égal à zéro et valide.');
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

			if (!Utils::checkDate($data['debut']))
			{
				throw new UserException('La date de début est invalide.');
			}

			if (!Utils::checkDate($data['fin']))
			{
				throw new UserException('La date de fin est invalide.');
			}
		}

		if (isset($data['id_categorie_compta']))
		{
			if ($data['id_categorie_compta'] != 0 && !$db->firstColumn('SELECT 1 FROM compta_categories WHERE id = ?;', (int) $data['id_categorie_compta']))
			{
				throw new UserException('Catégorie comptable inconnue');
			}

			$data['id_categorie_compta'] = (int) $data['id_categorie_compta'];
		}
	}

	/**
	 * Ajouter une cotisation
	 * @param array $data Tableau des champs à insérer
	 * @return integer ID de la cotisation créée
	 */
	public function add($data)
	{
		$db = DB::getInstance();

		$this->_checkFields($data);

		$db->insert('cotisations', $data);
		return $db->lastInsertRowId();
	}

	/**
	 * Modifier une cotisation
	 * @param  integer $id  ID de la cotisation à modifier
	 * @param  array $data Tableau des champs à modifier
	 * @return bool true si succès
	 */
	public function edit($id, $data)
	{
		$db = DB::getInstance();

        $this->_checkFields($data);

        return $db->update('cotisations', $data, 'id = :id', ['id' => (int) $id]);
	}

	/**
	 * Supprimer une cotisation
	 * @param  integer $id ID de la cotisation à supprimer
	 * @return integer true en cas de succès
	 */
	public function delete($id)
	{
		$db = DB::getInstance();

		$db->begin();

		// Les lignes liées dans les autres tables seront supprimées grâce à ON DELETE CASCADE
		$db->delete('cotisations', 'id = ?', (int) $id);

		$db->commit();

		return true;
	}

	/**
	 * Renvoie les infos sur une cotisation
	 * @param  integer $id Numéro de la cotisation
	 * @return array     Infos de la cotisation
	 */
	public function get($id)
	{
		$db = DB::getInstance();
		return $db->first('SELECT co.*,
			(SELECT COUNT(DISTINCT id_membre) FROM cotisations_membres WHERE id_cotisation = co.id) AS nb_membres,
			(SELECT COUNT(DISTINCT id_membre) FROM cotisations_membres AS cm WHERE id_cotisation = co.id
				AND ((co.duree IS NOT NULL AND date(cm.date, \'+\'||co.duree||\' days\') >= date())
					OR (co.fin IS NOT NULL AND co.debut <= cm.date AND co.fin >= cm.date))) AS nb_a_jour
			FROM cotisations AS co WHERE id = :id;', ['id' => (int) $id]);
	}

	public function listByName()
	{
		$db = DB::getInstance();
		return $db->get('SELECT * FROM cotisations ORDER BY intitule;');
	}

	public function listCurrent()
	{
		$db = DB::getInstance();
		return $db->get('SELECT * FROM cotisations WHERE fin >= date(\'now\') OR fin IS NULL
			ORDER BY transliterate_to_ascii(intitule) COLLATE NOCASE;');
	}

	public function listWithStats()
	{
		$db = DB::getInstance();
		return $db->get('SELECT co.*,
			(SELECT COUNT(DISTINCT id_membre) FROM cotisations_membres WHERE id_cotisation = co.id) AS nb_membres,
			(SELECT COUNT(DISTINCT id_membre) FROM cotisations_membres AS cm WHERE id_cotisation = co.id
				AND ((co.duree IS NOT NULL AND date(cm.date, \'+\'||co.duree||\' days\') >= date())
					OR (co.fin IS NOT NULL AND co.debut <= cm.date AND co.fin >= cm.date))) AS nb_a_jour
			FROM cotisations AS co
			ORDER BY transliterate_to_ascii(intitule) COLLATE NOCASE;');
	}
}
