<?php

namespace Garradin\Entities\Compta;

use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\DB;
use Garradin\Config;

class Mouvement extends Entity
{
	const TABLE = 'compta_mouvements';

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

	protected $_types = [
		'libelle'            => 'string',
		'remarques'          => '?string',
		'numero_piece'       => '?string',
		'date'               => 'date',
		'moyen_paiement'     => '?string',
		'reference_paiement' => '?string',
		'validation'         => 'bool',
		'hash'               => '?string',
		'prev_hash'          => '?string',
		'id_exercice'        => '?int',
		'id_auteur'          => '?int',
		'id_categorie'       => '?int',
		'id_projet'          => '?int',
	];

	protected $_validation_rules = [
		'libelle'            => 'required|string|max:200',
		'remarques'          => 'string|max:20000',
		'numero_piece'       => 'string|max:200',
		'date'               => 'required|date',
		'moyen_paiement'     => 'string|min:2|max:2|in_table:compta_moyens_paiement,code|required_with:id_categorie',
		'reference_paiement' => 'string|max:200',
		'validation'         => 'bool',
		'id_exercice'        => 'integer|in_table:compta_exercices,id',
		'id_auteur'          => 'integer|in_table:membres,id',
		'id_categorie'       => 'integer|in_table:compta_categories,id',
		'id_projet'          => 'integer|in_table:compta_projets,id'
	];

	protected $lignes;

	public function getLignes()
	{
		if (null === $this->lignes && $this->exists()) {
			$db = DB::getInstance();
			$this->lignes = $db->toObject($db->get('SELECT * FROM compta_mouvements_lignes WHERE id_mouvement = ? ORDER BY id;', $this->id), Ligne::class);
		}
		else {
			$this->lignes = [];
		}

		return $this->lignes;
	}

/*
	public function getHash()
	{
		if (!$this->id_exercice) {
			throw new \LogicException('Il n\'est pas possible de hasher un mouvement qui n\'est pas associé à un exercice');
		}

		static $keep_keys = [
			'libelle',
			'remarques',
			'numero_piece',
			'date',
			'moyen_paiement',
			'reference_paiement',
			'validation',
			'prev_hash',
		];

		$hash = hash_init('sha256');
		$values = $this->asArray();
		$values = array_intersect_key($values, $keep_keys);

		hash_update($hash, implode(',', array_keys($values)));
		hash_update($hash, implode(',', $values));

		foreach ($this->getLignes() as $ligne) {
			hash_update($hash, implode(',', [$ligne->compte, $ligne->debit, $ligne->credit]));
		}

		return hash_final($hash, false);
	}

	public function checkHash()
	{
		return hash_equals($this->getHash(), $this->hash);
	}
*/

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

	public function transfer(int $amount, int $from, int $to)
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
		if ($this->validation && !isset($this->_modified['validation'])) {
			throw new ValidationException('Il n\'est pas possible de modifier un mouvement qui a été validé');
		}

		if (!parent::save()) {
			return false;
		}

		foreach ($this->lignes as $ligne)
		{
			$ligne->id_mouvement = $this->id;
			$ligne->save();
		}
	}

	public function delete()
	{
		if ($this->validation) {
			throw new ValidationException('Il n\'est pas possible de supprimer un mouvement qui a été validé');
		}

		parent::delete();
	}

	public function filterUserValue($key, $value)
	{
		$value = parent::filterUserValue($key, $value);

		if ($key == 'moyen_paiement')
		{
			$value = strtoupper($value);
		}

		return $value;
	}

	public function selfCheck()
	{
		parent::selfCheck();

		$db = DB::getInstance();
		$config = Config::getInstance();

		// ID d'exercice obligatoire s'il existe déjà des exercices
		if (null === $this->id_exercice && $db->firstColumn('SELECT 1 FROM compta_exercices LIMIT 1;'))
		{
			throw new ValidationException('Aucun exercice spécifié.');
		}

		if (null !== $this->id_exercice
			&& !$db->test('compta_exercices', 'id = ? AND debut <= ? AND fin >= ?;', $this->id_exercice, $this->date, $this->date))
		{
			throw new ValidationException('La date ne correspond pas à l\'exercice sélectionné.');
		}

		$total = 0;

		$lignes = $this->getLignes();

		foreach ($lignes as $ligne) {
			$total += $ligne->credit;
			$total -= $ligne->debit;
		}

		if (0 !== $total) {
			throw new ValidationException('Mouvement non équilibré : déséquilibre entre débits et crédits');
		}
	}
}