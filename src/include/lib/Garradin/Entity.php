<?php

namespace Garradin;

use KD2\Form;
use KD2\AbstractEntity;

class Entity extends AbstractEntity
{
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
}
