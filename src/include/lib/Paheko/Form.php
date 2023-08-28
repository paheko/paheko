<?php

namespace Paheko;

class Form
{
	protected $errors = [];

	public function __construct()
	{
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