<?php

namespace Paheko\Entities;

use Paheko\Users\Session;
use Paheko\Entity;

class API_Credentials extends Entity
{
	const NAME = 'Identifiants API';

	const TABLE = 'api_credentials';

	protected ?int $id;
	protected string $label;
	protected string $key;
	protected string $secret;
	protected \DateTime $created;
	protected ?\DateTime $last_use;
	protected int $access_level;

	const ACCESS_LEVELS = [
		Session::ACCESS_READ => 'Peut lire les données',
		Session::ACCESS_WRITE => 'Peut lire et modifier les données',
		Session::ACCESS_ADMIN => 'Peut tout faire, y compris supprimer les données',
	];

	public function selfCheck(): void
	{
		parent::selfCheck();

		$this->assert(trim($this->label) !== '', 'La description ne peut être laissée vide.');
		$this->assert(trim($this->key) !== '', 'La clé ne peut être laissée vide.');
		$this->assert(trim($this->secret) !== '', 'Le secret ne peut être laissé vide.');
		$this->assert(array_key_exists($this->access_level, self::ACCESS_LEVELS));

		$this->assert(preg_match('/^[a-z0-9_]+$/', $this->key), 'L\'identifiant ne peut contenir que des lettres, des chiffres et des tirets bas.');
	}
}
