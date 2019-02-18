<?php

namespace Garradin\Compta;

use Garradin\Entity;
use Garradin\ValidationException;

class Ligne extends Entity
{
	protected $table = 'compta_mouvements_lignes';

	protected $id;
	protected $id_mouvement;
	protected $credit = 0;
	protected $debit = 0;

	const FIELDS = [
		'id_mouvement' => 'required|integer|in_table:compta_mouvements,id',
		'compte'       => 'required|alpha_num|in_table:compta_comptes,id',
		'credit'       => 'required|integer|min:0',
		'debit'        => 'required|integer|min:0'
	];

	public function filterUserEntry($key, $value)
	{
		$value = parent::filterUserEntry($key, $value);

		if ($key == 'credit' || $key == 'debit')
		{
			if (!preg_match('/^(\d+)(?:[,.](\d{2}))?$/', $value, $match))
			{
				throw new ValidationException('Le format du montant est invalide. Format accepté, exemple : 142,02');
			}

			$value = $match[1] . sprintf('%02d', $match[2]);
		}
		elseif ($key == 'compte')
		{
			$value = strtoupper($compte);
		}

		return $value;
	}

	public function selfCheck()
	{
		if (!$this->credit && !$this->debit)
		{
			throw new ValidationException('Aucun montant au débit ou au crédit.');
		}

		if (($this->credit * $this->debit) !== 0 || ($this->credit + $this->debit) !== 0)
		{
			throw new ValidationException('Ligne non équilibrée : crédit ou débit doit valoir zéro.');
		}

		if (!$this->id_mouvement)
		{
			throw new ValidationException('Aucun mouvement n\'a été indiqué pour cette ligne.');
		}
	}
}
