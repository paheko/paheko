<?php

namespace Garradin\Compta;

use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\DB;

class Mouvement extends Entity
{
	protected $table = 'compta_mouvements';

	protected $id;
	protected $libelle;
	protected $remarques;
	protected $numero_piece;

	protected $date;
	protected $moyen_paiement;
	protected $reference_paiement;

	protected $validation;

	protected $hash;
	protected $prev_hash;

	protected $id_exercice;
	protected $id_auteur;
	protected $id_categorie;
	protected $id_projet;

	const FIELDS = [
		'libelle'            => 'required|string',
		'remarques'          => 'string|max:20000',
		'numero_piece'       => 'string|max:200',
		'reference_paiement' => 'string|max:200',
		'date'               => 'required|date',
		'moyen_paiement'     => 'string|in_table:compta_moyens_paiement,code|required_with:id_categorie',
		'validation'         => 'bool',
		'id_exercice'        => 'integer|in_table:compta_exercices,id',
		'id_auteur'          => 'integer|in_table:membres,id',
		'id_categorie'       => 'integer|in_table:compta_categories,id',
		'id_projet'          => 'integer|in_table:compta_projets,id'
	];

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

	public function simple($montant, $moyen, $compte)
	{
		$this->moyen_paiement = $moyen;
		$categorie = new Categorie($this->id_categorie);

		if ($categorie->type == Categorie::DEPENSE)
		{
			$from = $categorie->compte;
			$to = $compte;
		}
		else
		{
			$from = $compte;
			$to = $categorie->compte;
		}

		return $this->transfer($montant, $from, $to);
	}

	public function transfer($amount, $from, $to)
	{
		$ligne1 = new Ligne;
		$ligne1->compte = $from;
		$ligne1->debit = $amount;
		$ligne1->credit = 0;

		$ligne2 = new Ligne;
		$ligne1->compte = $to;
		$ligne1->debit = 0;
		$ligne1->credit = $amount;

		return $this->add($ligne1) && $this->add($ligne2);
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

	public function filterUserEntry($key, $value)
	{
		$value = parent::filterUserEntry($key, $value);

		if ($key == 'moyen_paiement')
		{
			$value = strtoupper($value);
		}
		elseif ($key == 'date' && !is_object($value))
		{
			$value = new \DateTimeImmutable($value);
		}

		return $value;
	}

	public function selfCheck()
	{
		$db = DB::getInstance();
		$config = Config::getInstance();

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