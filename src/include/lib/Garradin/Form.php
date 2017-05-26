<?php

namespace Garradin;

class Form
{
	protected $errors = [];

	public function check($token_action = '', Array $rules = null)
	{
		if (!\KD2\Form::tokenCheck($token_action))
		{
			$this->errors[] = 'Une erreur est survenue, merci de bien vouloir renvoyer le formulaire.';
			return false;
		}

		if (!is_null($rules) && !$this->validate($rules))
		{
			return false;
		}

		return true;
	}

	public function validate(Array $rules)
	{
		return \KD2\Form::validate($rules, $this->errors, $_POST);
	}

	public function hasErrors()
	{
		return (count($this->errors) > 0);
	}

	public function &getErrors()
	{
		return $this->errors;
	}

	public function addError($msg)
	{
		$this->errors[] = $msg;
	}

	public function getErrorMessages($membre = false)
	{
		$errors = [];

		if ($membre)
		{
			$champs = Config::getInstance()->get('champs_membres');
		}

		foreach ($this->errors as $error)
		{
			if (is_array($error))
			{
				if ($membre)
				{
					$error['name'] = $champs->get($error['name'], 'title');
				}

				$errors[] = $this->getErrorMessage($error['rule'], $error['name'], $error['params']);
			}
			else
			{
				$errors[] = $error;
			}
		}

		return $errors;
	}

	protected function getErrorMessage($rule, $element, Array $params)
	{
		if ($element == '_id')
		{
			$element = 'identifiant';
		}
		elseif ($element == 'passe')
		{
			$element = 'mot de passe';
		}

		switch ($rule)
		{
			case 'required':
				return sprintf('Le champ %s est vide.', $element);
			case 'min':
				return sprintf('Le champ %s doit faire au moins %d caractères.', $element, $params[0]);
			case 'file':
				return sprintf('Le fichier envoyé n\'est pas valide.');
			case 'confirmed':
				return sprintf('La vérification du champ %s n\'est pas identique au champ lui-même.', $element);
			case 'date_format':
				return sprintf('Format de date invalide dans le champ %s.', $element);
			default:
				return sprintf('Erreur "%s" dans le champ "%s"', $rule, $element);
		}
	}

	public function __invoke($key)
	{
		return \KD2\Form::get($key);
	}
}