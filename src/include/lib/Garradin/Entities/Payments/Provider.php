<?php

namespace Garradin\Entities\Payments;

use Garradin\Entity;
use Garradin\Config;
use Garradin\Entities\Users\User;
use Garradin\Users\DynamicFields;

use KD2\DB\EntityManager as EM;

class Provider extends Entity
{
	const TABLE = 'payment_providers';
	const DEFAULT_USER_NAME = 'Extension %s';

	protected ?int		$id;
	protected ?int		$id_user;
	protected string	$name;
	protected string	$label;
	protected ?string	$_user_name = null;

	public function save(bool $selfcheck = true): bool
	{
		if (!$this->exists()) {
			$user = new User();
			$user->set('id_category', (int)Config::getInstance()->providers_category);
			$user->set(DynamicFields::getFirstNameField(), $this->_user_name ?? sprintf(self::DEFAULT_USER_NAME, $this->label));
			$user->setNumberIfEmpty();
			$user->save();

			$this->set('id_user', (int)$user->id);
		}
		return parent::save($selfcheck);
	}

	public function delete(): bool
	{
		$user = EM::findOneById(User::class, $this->id_user);
		$user->delete();
		return parent::delete();
	}

	// Called when the provider is not a plugin
	public function setUserName(string $user_name): void
	{
		$this->_user_name = $user_name;
	}
}
