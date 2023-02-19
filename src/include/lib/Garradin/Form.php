<?php

namespace Garradin;

class Form
{
	protected $errors = [];

	public function __construct()
	{
		// Valide un montant de monnaie valide (deux décimales, ne peut être négatif)
		\KD2\Form::registerValidationRule('money', function ($name, $params, $value) {
			return preg_match('/^\d+(?:[.,]\d{1,2})?$/', $value) && $value >= 0;
		});

		// Test si la valeur existe dans cette table
		// in_table:compta_categories,id
		\KD2\Form::registerValidationRule('in_table', function ($name, $params, $value) {
			$db = DB::getInstance();
			return $db->test($params[0], $db->where($params[1], $value));
		});

		// Catch file size https://stackoverflow.com/questions/2133652/how-to-gracefully-handle-files-that-exceed-phps-post-max-size
		if (empty($_FILES) && empty($_POST)
			&& isset($_SERVER['REQUEST_METHOD'])
			&& !empty($_SERVER['CONTENT_LENGTH'])
			&& strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
			$this->addError('Le fichier envoyé dépasse la taille autorisée');
		}
	}

	public function run(callable $fn, ?string $csrf_key = null, ?string $redirect = null, bool $follow_redirect = false): bool
	{
		try {
			if (null !== $csrf_key && !\KD2\Form::tokenCheck($csrf_key)) {
				throw new ValidationException('Une erreur est survenue, merci de bien vouloir renvoyer le formulaire.');
			}

			call_user_func($fn);

			if (null !== $redirect) {
				if (array_key_exists('_dialog', $_GET)) {
					Utils::reloadParentFrame($follow_redirect ? $redirect : null);
				}

				Utils::redirect($redirect);
			}

			return true;
		}
		catch (UserException $e) {
			$this->addError($e);

			Form::reportUserException($e);

			return false;
		}
	}

	static public function reportUserException(UserException $e): void
	{
		if (REPORT_USER_EXCEPTIONS === 2) {
			throw $e;
		}
		elseif (REPORT_USER_EXCEPTIONS === 1) {
			\KD2\ErrorManager::reportExceptionSilent($e);
		}
	}

	public function runIf($condition, callable $fn, ?string $csrf_key = null, ?string $redirect = null, bool $follow_redirect = false): ?bool
	{
		if (is_string($condition) && empty($_POST[$condition])) {
			return null;
		}
		elseif (is_bool($condition) && !$condition) {
			return null;
		}

		return $this->run($fn, $csrf_key, $redirect, $follow_redirect);
	}

	/**
	 * @deprecated
	 */
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

	/**
	 * @deprecated
	 */
	public function validate(Array $rules, array $source = null)
	{
		return \KD2\Form::validate($rules, $this->errors, $source);
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

	public function getErrorMessages()
	{
		return $this->errors;
	}

	public function __invoke($key)
	{
		return \KD2\Form::get($key);
	}

	/**
	 * Returns a value from a custom list selector
	 * see CommonFunctions::input
	 */
	static public function getSelectorValue($value) {
		if (!is_array($value)) {
			return $value;
		}

		$values = array_filter(array_keys($value));

		if (count($values) == 1) {
			return current($values);
		}
		elseif (!count($values)) {
			return ''; // Empty
		}
		else {
			return $values;
		}
	}
}