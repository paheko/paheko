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
		if (null === $source) {
			$source = $_POST;
		}

		$form = new Form;

		if (!$form->validate($this->_form_rules, $source))
		{
			$messages = $form->getErrorMessages();

			throw new ValidationException(implode("\n", $messages));
		}

		return $this->import($source);
	}

	protected function filterUserValue(string $type, $value, string $key)
	{
		if ($type == 'date') {
			return \DateTime::createFromFormat('d/m/Y', $value);
		}
		else {
			return parent::filterUserValue($type, $value, $key);
		}
	}

	protected function assert(bool $test, string $message = null): void
	{
		if (null !== $message && !$test) {
			throw new ValidationException($message);
		}
	}
}
