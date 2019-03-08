<?php

namespace Garradin;

use KD2\Form;

class Entity
{
	const FIELDS = [];

	protected $id;
	protected $table;
	protected $modified = [];

	public function __construct($id = null)
	{
		if (null === $this->table)
		{
			throw new \LogicException('Aucun nom de table spécifié.');
		}

		if (null !== $id)
		{
			$result = DB::getInstance()->first('SELECT * FROM ' . $this->table . ' WHERE id = ?;', $id);

			foreach ($result as $key => $value)
			{
				$this->$key = $value;
			}
		}
	}

	public function save()
	{
		if (!count($this->modified))
		{
			return true;
		}

		$this->selfValidate();
		$this->selfCheck();

		$db = DB::getInstance();

		if (null === $this->id)
		{
			if ($return = $db->insert($this->table, $this->toArray()))
			{
				$this->id = $db->lastInsertId();
			}
		}
		else
		{
			$return = $db->update($this->table, $this->modified, 'id = :id', ['id' => $this->id]);
		}

		$this->modified = [];

		return $return;
	}

	/**
	 * Vérifier la cohérence de l'objet avant enregistrement
	 */
	public function selfCheck()
	{
		return true;
	}

	/**
	 * Valider les champs avant enregistrement
	 * @throws ValidationException Si une erreur de validation survient
	 */
	public function selfValidate()
	{
		$errors = [];

		if (!Form::validate($this->_fields, $errors, $this->toArray()))
		{
			$messages = [];

			foreach ($errors as $error)
			{
				$messages[] = $this->getValidationMessage($error);
			}

			throw new ValidationException(implode("\n", $messages));
		}
	}

	public function set($fields = null)
	{
		foreach ($fields as $key => $value)
		{
			if (!$this->__set($key, $value))
			{
				return false;
			}
		}

		return true;
	}

	public function __get($key)
	{
		return $this->$key;
	}

	public function __set($key, $value)
	{
		if (!in_array($key, $this->_fields))
		{
			throw new ValidationException(sprintf('Le champ "%s" ne peut être modifié.', $key));
		}

		$value = $this->filterUserEntry($key, $value);

		$this->$key = $value;
		$this->modified[$key] = $value;
	}

	public function __isset($key)
	{
		return property_exists($this, $key);
	}

	/**
	 * Filtrer/sanitiser la valeur entrée par l'utilisateur pour un champ de l'entité
	 * (effectué au set)
	 */
	public function filterUserEntry($key, $value)
	{
		return trim($value);
	}

	public function toArray()
	{
		$out = [];

		foreach ($this->_fields as $key)
		{
			$out[$key] = $this->$key;
		}

		return $out;
	}
}
