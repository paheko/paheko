<?php

namespace Garradin\Compta;

use Garradin\DB;
use Garradin\Utils;
use Garradin\ValidationException;

class Compte_Bancaire extends Compte
{
	protected $banque;
	protected $iban;
	protected $bic;

	protected $_extra_fields = [
		'banque' => 'required|string',
		'iban'   => 'alpha_num|max:34',
		'bic'    => 'alpha_num|max:11|min:8'
	];

	public function __construct($id = null)
	{
		$this->_fields = array_merge($this->_fields, $this->_extra_fields);
		parent::__construct($id);
	}

	public function filterUserEntry($key, $value)
	{
		$value = parent::filterUserEntry($key, $value);

		if ($key == 'iban' || $key == 'bic')
		{
			// Ne garder que les lettres et chiffres
			$value = preg_replace('![^\dA-Z]!', '', strtoupper($value));
		}

		return $value;
	}

	public function selfValidate()
	{
		parent::selfValidate();

		if (null !== $this->iban && !Utils::checkIBAN($this->iban))
		{
			throw new ValidationException('Code IBAN invalide');
		}

		if (null !== $this->bic && !Utils::checkBIC($this->bic))
		{
			throw new ValidationException('Code BIC/SWIFT invalide');
		}
	}
}