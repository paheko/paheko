<?php

namespace Garradin;

class Form
{
	protected $errors = [];

	public function check($token_action = '')
	{
		if (!\KD2\Form::tokenCheck($token_action))
		{
			$this->errors[] = 'Erreur CSRF';
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

	public function getErrorMessages()
	{
		return;
	}

	public function __invoke($key)
	{
		return \KD2\Form::get($key);
	}
}