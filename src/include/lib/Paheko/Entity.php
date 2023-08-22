<?php

namespace Paheko;

use Paheko\Form;
use KD2\DB\AbstractEntity;
use KD2\DB\Date;

use DateTime;

class Entity extends AbstractEntity
{
	/**
	 * Entity name (eg. "Accounting transaction")
	 * Entities with no name won't be stored in action logs
	 */
	const NAME = null;

	/**
	 * Entity admin URL
	 */
	const PRIVATE_URL = null;

	/**
	 * Valider les champs avant enregistrement
	 * @throws ValidationException Si une erreur de validation survient
	 */
	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		try {
			return $this->import($source);
		}
		catch (\UnexpectedValueException $e) {
			throw new ValidationException($e->getMessage(), 0, $e);
		}
	}

	static public function filterUserDateValue(?string $value): ?\DateTime
	{
		$value = trim((string) $value);

		if (!$value) {
			return null;
		}

		if (ctype_digit($value)) {
			return new DateTime('@' . $value);
		}
		elseif ($v = \DateTime::createFromFormat('!Y-m-d H:i:s', $value)) {
			return $v;
		}
		elseif ($v = \DateTime::createFromFormat('!Y-m-d H:i', $value)) {
			return $v;
		}
		elseif ($v = Date::createFromFormat('!Y-m-d', $value)) {
			return $v;
		}
		elseif (preg_match('!^\d{2}/\d{2}/\d{2}$!', $value)) {
			$year = substr($value, -2);

			// Make sure recent years are in the 21st century
			if ($year < date('y') + 10) {
				$year = sprintf('20%02d', $year);
			}
			// while old dates remain in the old one
			else {
				$year = sprintf('19%02d', $year);
			}

			return Date::createFromFormat('d/m/Y', substr($value, 0, -2) . $year);
		}
		elseif (preg_match('!^\d{2}/\d{2}/\d{4}$!', $value)) {
			return Date::createFromFormat('d/m/Y', $value);
		}
		elseif (preg_match('!^\d{4}/\d{2}/\d{2}$!', $value)) {
			return Date::createFromFormat('Y/m/d', $value);
		}
		elseif (preg_match('!^20\d{2}[01]\d[0123]\d$!', $value)) {
			return Date::createFromFormat('Ymd', $value);
		}
		elseif (null !== $value) {
			throw new ValidationException('Format de date invalide (merci d\'utiliser le format JJ/MM/AAAA) : ' . $value);
		}

		return null;
	}

	protected function filterUserValue(string $type, $value, string $key)
	{
		if ($type == 'date' || $type == Date::class) {
			if ($value instanceof Date) {
				return $value;
			}
			elseif ($value instanceof \DateTimeInterface) {
				return Date::createFromInterface($value);
			}

			$d = self::filterUserDateValue($value);

			if (!$d) {
				return $d;
			}

			$y = $d->format('Y');
			if ($y < 1900 || $y > 2100) {
				throw new ValidationException(sprintf('Date invalide (%s) : doit être entre 1900 et 2100', $key));
			}

			return $d;

		}
		elseif ($type == 'DateTime' && is_string($value)) {
			if (preg_match('!^\d{2}/\d{2}/\d{4}\s\d{1,2}:\d{2}$!', $value)) {
				return \DateTime::createFromFormat('!d/m/Y H:i', $value);
			}
		}

		return parent::filterUserValue($type, $value, $key);
	}

	protected function assert($test, string $message = null, int $code = 0): void
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
			throw new ValidationException($message, $code);
		}
	}

	// Add plugin signals to save/delete
	public function save(bool $selfcheck = true): bool
	{
		$name = get_class($this);
		$name = str_replace('Paheko\Entities\\', '', $name);
		$name = 'entity.' . $name . '.save';

		// We are doing selfcheck here before sending the before event
		if ($selfcheck) {
			$this->selfCheck();
		}

		$new = $this->exists() ? false : true;
		$modified = $this->isModified();
		$entity = $this;

		$signals = [
			// Specific entity signal
			'entity.' . $name . '.save',
			// Generic entity signal
			'entity.save',
		];

		if ($new) {
			$signals[] = 'entity.' . $name . '.create';
			$signals[] = 'entity.create';
		}
		elseif ($modified) {
			$signals[] = 'entity.' . $name . '.modify';
			$signals[] = 'entity.modify';
		}

		$params = compact('entity', 'new', 'modified');

		foreach ($signals as $signal_name) {
			$signal = Plugins::fire($signal_name . '.before', true, $params);

			if ($signal && $signal->isStopped()) {
				return true;
			}
		}

		$params['success'] = parent::save(false);

		// Log creation/edit, but don't record stuff that doesn't change anything
		if ($this::NAME && ($new || $modified)) {
			Log::add($new ? Log::CREATE : Log::EDIT, ['entity' => get_class($this), 'id' => $this->id()]);
		}


		foreach ($signals as $signal_name) {
			Plugins::fire($signal_name . '.after', false, $params);
		}

		return $params['success'];
	}

	public function delete(): bool
	{
		$type = get_class($this);
		$type = str_replace('Paheko\Entities\\', '', $type);
		$name = 'entity.' . $type . '.delete';

		$id = $this->id();
		$entity = $this;

		// Specific entity signal
		$signal = Plugins::fire($name . '.before', true, compact('entity', 'id'));

		if ($signal && $signal->isStopped()) {
			return true;
		}

		// Generic entity signal
		$signal = Plugins::fire('entity.delete.before', true, compact('entity', 'id'));

		if ($signal && $signal->isStopped()) {
			return true;
		}

		$success = parent::delete();

		if ($this::NAME) {
			Log::add(Log::DELETE, ['entity' => get_class($this), 'id' => $id]);
		}

		Plugins::fire($name . '.after', false, compact('entity', 'success', 'id'));
		Plugins::fire('entity.delete.after', false, compact('entity', 'success', 'id'));

		return $success;
	}
}
