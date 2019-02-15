<?php

namespace Garradin\Compta;

use Garradin\Entity;

class Mouvement extends Entity
{
	protected $table = 'compta_mouvements';

	protected $id;
	protected $libelle;
	protected $remarques;
	protected $numero_piece;

	protected $date;
	protected $moyen_paiement;
	protected $numero_cheque;

	protected $validation;

	protected $hash;
	protected $prev_hash;

	protected $id_exercice;
	protected $id_auteur;
	protected $id_categorie;
	protected $id_projet;

	protected $lignes = [];

	public function getLignes()
	{
		$db = DB::getInstance();
		return $db->toObject($db->get('SELECT * FROM compta_mouvements_lignes WHERE id_mouvement = ? ORDER BY id;', $this->id), Ligne::class);
	}

	public function add(Ligne $ligne)
	{
		$this->lignes[] = $ligne;
	}

	public function save()
	{
		if (!parent::save())
		{
			return false;
		}

		foreach ($this->lignes as $ligne)
		{
			$ligne->id_mouvement = $this->id;
			$ligne->save();
		}
	}

	public function validate($key, $value)
	{
		switch ($key)
		{
			case 'date':
				if (!($value instanceof \DateTime))
				{
					throw new ValidationException('La date est invalide.');
				}
				break;
			case 'moyen_paiement':
				if (!$db->test('compta_moyens_paiement', 'code', $value))
				{
					throw new ValidationException('Moyen de paiement inconnu.');
				}
				break;
			case 'id_exercice':
				if (null !== $value && !$db->test('compta_exercices', 'id', $value))
				{
					throw new ValidationException('Numéro d\'exercice invalide.');
				}
				break;
			default:
				break;
		}

		return true;
	}

	public function selfCheck()
	{
		if (trim($this->libelle) === '')
		{
			throw new ValidationException('Le libellé ne peut rester vide.');
		}

		if (null === $this->date)
		{
			throw new ValidationException('Le date ne peut rester vide.');
		}

		if (null === $this->id_exercice && $config->get('compta_expert'))
		{
			throw new ValidationException('Aucun exercice spécifié.');
		}

		if (null !== $this->id_exercice 
			&& !$db->test('compta_exercices', 'id = ? AND debut <= ? AND fin >= ?;', $this->id_exercice, $this->date, $this->date))
		{
			throw new ValidationException('La date ne correspond pas à l\'exercice sélectionné.');
		}
	}
}