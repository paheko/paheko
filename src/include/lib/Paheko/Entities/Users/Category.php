<?php

namespace Paheko\Entities\Users;

use Paheko\Users\Session;
use Paheko\Config;
use Paheko\DB;
use Paheko\Entity;
use Paheko\UserException;
use Paheko\Utils;

class Category extends Entity
{
	const NAME = 'Catégorie de membre';
	const PRIVATE_URL = '!config/categories/edit.php?id=%d';

	const TABLE = 'users_categories';

	protected int $id;
	protected string $name;

	protected bool $hidden = false;

	protected int $perm_web = 0;
	protected int $perm_documents = 0;
	protected int $perm_users = 0;
	protected int $perm_accounting = 0;

	protected int $perm_subscribe = 0;
	protected int $perm_connect = 0;
	protected int $perm_config = 0;

	const PERMISSIONS = [
		'connect' => [
			'label' => 'Les membres de cette catégorie peuvent-ils se connecter ?',
			'shape' => Utils::ICONS['login'],
			'options' => [
				Session::ACCESS_NONE => 'N\'a pas le droit de se connecter',
				Session::ACCESS_READ => 'A le droit de se connecter',
			],
		],
		'users' => [
			'label' => 'Gestion des membres',
			'shape' => Utils::ICONS['users'],
			'options' => [
				Session::ACCESS_NONE => 'Pas d\'accès',
				Session::ACCESS_READ => 'Lecture uniquement (peut voir les informations personnelles de tous les membres, y compris leurs inscriptions à des activités)',
				Session::ACCESS_WRITE => 'Lecture & écriture (peut ajouter et modifier des membres, mais pas les supprimer ni les changer de catégorie, peut inscrire des membres à des activités, peut envoyer des messages collectifs)',
				Session::ACCESS_ADMIN => 'Administration (peut tout faire)',
			],
		],
		'accounting' => [
			'label' => 'Comptabilité',
			'shape' => Utils::ICONS['money'],
			'options' => [
				Session::ACCESS_NONE => 'Pas d\'accès',
				Session::ACCESS_READ => 'Lecture uniquement (peut lire toutes les informations de tous les exercices)',
				Session::ACCESS_WRITE => 'Lecture & écriture (peut ajouter des écritures, mais pas les modifier ni les supprimer)',
				Session::ACCESS_ADMIN => 'Administration (peut tout faire)',
			],
		],
		'documents' => [
			'label' => 'Documents',
			'shape' => Utils::ICONS['folder'],
			'options' => [
				Session::ACCESS_NONE => 'Pas d\'accès',
				Session::ACCESS_READ => 'Lecture uniquement (peut lire tous les fichiers)',
				Session::ACCESS_WRITE => 'Lecture & écriture (peut ajouter, modifier et déplacer des fichiers, mais pas les supprimer)',
				Session::ACCESS_ADMIN => 'Administration (peut tout faire, notamment mettre des fichiers dans la corbeille)',
			],
		],
		'web' => [
			'label' => 'Gestion du site web',
			'shape' => Utils::ICONS['globe'],
			'options' => [
				Session::ACCESS_NONE => 'Pas d\'accès',
				Session::ACCESS_READ => 'Lecture uniquement (peut lire les pages)',
				Session::ACCESS_WRITE => 'Lecture & écriture (peut ajouter et modifier des pages et catégories, mais pas les supprimer)',
				Session::ACCESS_ADMIN => 'Administration (peut tout faire)',
			],
		],
		'config' => [
			'label' => 'Les membres de cette catégorie peuvent-ils modifier la configuration ?',
			'shape' => Utils::ICONS['settings'],
			'options' => [
				Session::ACCESS_NONE => 'Ne peut pas modifier la configuration',
				Session::ACCESS_ADMIN => 'Peut modifier la configuration',
			],
		],
	];

	public function selfCheck(): void
	{
		parent::selfCheck();

		$this->assert(trim($this->name) !== '', 'Le nom de catégorie ne peut rester vide.');

		foreach (self::PERMISSIONS as $key => $perm) {
			$this->assert(array_key_exists($this->{'perm_' . $key}, $perm['options']), 'Invalid value for perm_' . $key);
		}
	}

	public function delete(): bool
	{
		$db = DB::getInstance();
		$config = Config::getInstance();

		if ($this->id() == $config->get('default_category')) {
			throw new UserException('Il est interdit de supprimer la catégorie définie par défaut dans la configuration.');
		}

		if ($db->test('users', 'id_category = ?', $this->id())) {
			throw new UserException('La catégorie contient encore des membres, il n\'est pas possible de la supprimer.');
		}

		return parent::delete();
	}

	public function setAllPermissions(int $access): void
	{
		foreach (self::PERMISSIONS as $key => $perm) {
			// Restrict to the maximum access level, as some permissions only allow up to READ
			$perm_access = min($access, max(array_keys($perm['options'])));
			$this->set('perm_' . $key, $perm_access);
		}
	}

	public function getPermissions(): array
	{
		$out = [];

		foreach (self::PERMISSIONS as $key => $perm) {
			$out[$key] = $this->{'perm_' . $key};
		}

		return $out;
	}

	public function count(): int
	{
		return DB::getInstance()->count(self::TABLE, 'id = ?', $this->id);
	}
}
