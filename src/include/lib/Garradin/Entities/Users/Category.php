<?php

namespace Garradin\Entities\Users;

use Garradin\Membres\Session;
use Garradin\Config;
use Garradin\DB;
use Garradin\UserException;
use Garradin\Entity;

class Category extends Entity
{
	const TABLE = 'users_categories';

	protected $id;
	protected $name;

	protected $hidden;

	protected $perm_web;
	protected $perm_documents;
	protected $perm_users;
	protected $perm_accounting;

	protected $perm_subscribe;
	protected $perm_connect;
	protected $perm_config;

	protected $_types = [
		'id'              => 'int',
		'name'            => 'string',
		'hidden'          => 'int',
		'perm_web'        => 'int',
		'perm_documents'  => 'int',
		'perm_users'      => 'int',
		'perm_accounting' => 'int',
		'perm_subscribe'  => 'int',
		'perm_subscribe'  => 'int',
		'perm_connect'    => 'int',
		'perm_config'     => 'int',
	];

	const PERMISSIONS = [
		'connect' => [
			'label' => 'Les membres de cette catégorie peuvent-ils se connecter ?',
			'shape' => 'C',
			'options' => [
				Session::ACCESS_NONE => 'N\'a pas le droit de se connecter',
				Session::ACCESS_READ => 'A le droit de se connecter',
			],
		],
		'users' => [
			'label' => 'Gestion des membres',
			'shape' => 'M',
			'options' => [
				Session::ACCESS_NONE => 'Pas d\'accès',
				Session::ACCESS_READ => 'Lecture uniquement (peut voir les informations personnelles de tous les membres, y compris leurs inscriptions à des activités)',
				Session::ACCESS_WRITE => 'Lecture & écriture (peut ajouter et modifier des membres, mais pas les supprimer ni les changer de catégorie, peut inscrire des membres à des activités)',
				Session::ACCESS_ADMIN => 'Administration (peut tout faire)',
			],
		],
		'accounting' => [
			'label' => 'Comptabilité',
			'shape' => '€',
			'options' => [
				Session::ACCESS_NONE => 'Pas d\'accès',
				Session::ACCESS_READ => 'Lecture uniquement (peut lire toutes les informations de tous les exercices)',
				Session::ACCESS_WRITE => 'Lecture & écriture (peut ajouter des écritures, mais pas les modifier ni les supprimer)',
				Session::ACCESS_ADMIN => 'Administration (peut modifier et supprimer des écritures, gérer les comptes, les exercices, etc.)',
			],
		],
		'documents' => [
			'label' => 'Documents',
			'shape' => 'D',
			'options' => [
				Session::ACCESS_NONE => 'Pas d\'accès',
				Session::ACCESS_READ => 'Lecture uniquement (peut lire tous les fichiers)',
				Session::ACCESS_WRITE => 'Lecture & écriture (peut lire, ajouter, modifier et déplacer des fichiers, mais pas les supprimer)',
				Session::ACCESS_ADMIN => 'Administration (peut tout faire)',
			],
		],
		'web' => [
			'label' => 'Gestion du site web',
			'shape' => 'W',
			'options' => [
				Session::ACCESS_NONE => 'Pas d\'accès',
				Session::ACCESS_READ => 'Lecture uniquement (peut lire les pages)',
				Session::ACCESS_WRITE => 'Lecture & écriture (peut ajouter, modifier et supprimer des pages et catégories, mais pas modifier la configuration)',
				Session::ACCESS_ADMIN => 'Administration (peut tout faire)',
			],
		],
		'config' => [
			'label' => 'Les membres de cette catégorie peuvent-ils modifier la configuration ?',
			'shape' => '☑',
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
		$this->assert($this->hidden === 0 || $this->hidden === 1, 'Wrong value for hidden');

		static $permissions = [Session::ACCESS_NONE, Session::ACCESS_READ, Session::ACCESS_WRITE, Session::ACCESS_ADMIN];

		foreach ($this->_types as $key => $type) {
			if (substr($key, 0, 5) != 'perm_') {
				continue;
			}

			$this->assert(in_array($this->$key, $permissions, true), 'Invalid value for ' . $key);
		}
	}

	public function delete(): bool
	{
		$db = DB::getInstance();
		$config = Config::getInstance();

		if ($this->id() == $config->get('categorie_membres')) {
			throw new UserException('Il est interdit de supprimer la catégorie définie par défaut dans la configuration.');
		}

		if ($db->test('membres', 'category_id = ?', $this->id())) {
			throw new UserException('La catégorie contient encore des membres, il n\'est pas possible de la supprimer.');
		}

		return parent::delete();
	}

	public function setAllPermissions(int $access): void
	{
		foreach ($this->_types as $key => $type) {
			if (substr($key, 0, 5) != 'perm_') {
				continue;
			}

			$this->set($key, $access);
		}
	}
}
