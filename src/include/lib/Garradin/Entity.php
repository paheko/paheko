<?php

namespace Garradin;

use Garradin\Form;
use KD2\DB\AbstractEntity;

class Entity extends AbstractEntity
{
	protected $_form_rules = [];

	/**
	 * Valider les champs avant enregistrement
	 * @throws ValidationException Si une erreur de validation survient
	 */
	public function importForm(array $source = null)
	{
		$form = new Form;

		if (!$form->validate($this->_form_rules, $source))
		{
			$messages = $form->getErrorMessages();

			throw new ValidationException(implode("\n", $messages));
		}

		return $this->import($source);
	}

	protected function assert(bool $test, string $message = null): void
	{
		if (null !== $message && !$test) {
			throw new ValidationException($message);
		}
	}
}
