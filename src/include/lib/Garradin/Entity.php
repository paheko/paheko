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
			if (!trim($value)) {
				return null;
			}

			if (preg_match('!^\d{2}/\d{2}/\d{2}$!', $value)) {
				return \DateTime::createFromFormat('d/m/y', $value);
			}
			elseif (preg_match('!^\d{2}/\d{2}/\d{4}$!', $value)) {
				return \DateTime::createFromFormat('d/m/Y', $value);
			}
			elseif (null !== $value) {
				throw new ValidationException('Format de date invalide (merci d\'utiliser le format JJ/MM/AAAA) : ' . $value);
			}
		}
		elseif ($type == 'DateTime') {
			if (preg_match('!^\d{2}/\d{2}/\d{4}\s\d{2}:\d{2}$!', $value)) {
				return \DateTime::createFromFormat('d/m/Y H:i', $value);
			}
		}

		return parent::filterUserValue($type, $value, $key);
	}

	protected function assert(?bool $test, string $message = null): void
	{
		if ($test) {
			return;
		}

		if (null === $message) {
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			$caller_class = array_pop($backtrace);
			$caller = array_pop($backtrace);
			$message = sprintf('Entity assertion fail from class %s on line %d', $caller_class['class'], $caller['line']);
			throw new \UnexpectedValueException($message);
		}
		else {
			throw new ValidationException($message);
		}
	}
}
