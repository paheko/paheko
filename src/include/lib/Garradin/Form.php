<?php

namespace Garradin;

class Form
{
	protected $errors = [];

	public function __construct()
	{
		// Valide un montant de monnaie valide (deux décimales, ne peut être négatif)
		\KD2\Form::registerValidationRule('money', function ($name, $params, $value) {
			return preg_match('/^\d+(?:\.\d{1,2})?$/', $value) && $value >= 0;
		});

		// Test si la valeur existe dans cette table
		// in_table:compta_categories,id
		\KD2\Form::registerValidationRule('in_table', function ($name, $params, $value) {
			$db = DB::getInstance();
			return $db->test($params[0], $db->where($params[1], $value));
		});
	}

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

	protected function getFieldName($name)
	{
		switch ($name)
		{
			case '_id': return 'identifiant';
			case 'passe': return 'mot de passe';
			case 'debut': return 'date de début';
			case 'fin': return 'date de fin';
			case 'duree': return 'durée';
			case 'passe_check': return 'vérification de mot de passe';
			default: return $name;
		}
	}

	protected function getErrorMessage($rule, $element, Array $params)
	{
		$element = $this->getFieldName($element);

		switch ($rule)
		{
			case 'required':
			case 'required_if':
			case 'required_unless':
			case 'required_with':
			case 'required_with_all':
			case 'required_without':
			case 'required_without_all':
				return sprintf('Le champ %s est vide.', $element);
			case 'min':
				return sprintf('Le champ %s doit faire au moins %d caractères.', $element, $params[0]);
			case 'file':
				return sprintf('Le fichier envoyé n\'est pas valide.');
			case 'confirmed':
				return sprintf('La vérification du champ %s n\'est pas identique au champ lui-même.', $element);
			case 'date_format':
				return sprintf('Format de date invalide dans le champ %s.', $element);
			case 'numeric':
				return sprintf('Le champ %s doit être un nombre.', $element);
			case 'money':
				return sprintf('Le champ %s n\'est pas un nombre valide.', $element);
			default:
				return sprintf('Erreur "%s" dans le champ "%s"', $rule, $element);
		}
	}

	public function __invoke($key)
	{
		return \KD2\Form::get($key);
	}
}