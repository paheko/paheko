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

		if (empty($data['montant']) || !is_numeric($data['montant']))
		{
			throw new UserException('Le montant doit être un nombre valide.');
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
	 * @param [type] $data [description]
	 */
	public function add($data)
	{
		$db = DB::getInstance();

		$this->_checkFields($data);

		$db->simpleInsert('transactions', $data);
		$id = $db->lastInsertRowId();

		return $id;
	}

	public function edit($id, $data)
	{
		$db = DB::getInstance();

        $this->_checkFields($data);

        return $db->simpleUpdate('transactions', $data, 'id = \''.(int) $id.'\'');
	}

	public function delete($id)
	{
		$db = DB::getInstance();
		return $db->simpleExec('DELETE FROM transactions WHERE id = ?;', (int) $id);
	}

	public function get($id)
	{
		$db = DB::getInstance();
		return $db->simpleQuerySingle('SELECT * FROM transactions WHERE id = ?;', true, (int) $id);
	}

	public function listByName()
	{
		return $db->simpleStatementFetch('SELECT * FROM transactions ORDER BY intitule;')
	}
}

?>