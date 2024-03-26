<?php

namespace Paheko;

use Paheko\Form;
use KD2\DB\AbstractEntity;
use KD2\DB\Date;
use KD2\ErrorManager;

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
		$source ??= $_POST;

		try {
			return $this->import($source);
		}
		catch (\UnexpectedValueException $e) {
			throw new ValidationException($e->getMessage(), 0, $e);
		}
	}

	static public function filterUserDateValue($value, string $class = \DateTime::class): ?\DateTime
	{
		if (null === $value) {
			return null;
		}
		elseif ('' === $value) {
			return null;
		}
		elseif (is_object($value)) {
			if ($value instanceof $class) {
				return $value;
			}
			elseif ($class === Date::class && $value instanceof \DateTimeInterface) {
				return Date::createFromInterface($value);
			}
			elseif ($class === \DateTime::class && $value instanceof \DateTimeInterface) {
				return $value;
			}
			else {
				throw new \InvalidArgumentException('Invalid argument, not a valid date object: ' . get_class($value));
			}
		}

		$value = trim((string) $value);

		if (!$value) {
			return null;
		}

		$format = null;
		$date = null;

		$a = substr($value, 2, 1);
		$b = substr($value, 4, 1);
		$l = strlen($value);

		if ($a === '/') {
			// DD/MM/YY
			if ($l === 8) {
				$year = substr($value, -2);

				// Make sure recent years are in the 21st century
				if ($year < date('y') + 10) {
					$year = sprintf('20%02d', $year);
				}
				// while old dates remain in the old one
				else {
					$year = sprintf('19%02d', $year);
				}

				$format = '!d/m/Y';
				$value = substr($value, 0, -2) . $year;
			}
			// DD/MM/YYYY
			elseif ($l === 10) {
				$format = '!d/m/Y';
			}
			// DD/MM/YYYY HH:MM
			elseif ($l === 16) {
				$format = '!d/m/Y H:i';
			}
			// DD/MM/YYYY HH:MM:SS
			elseif ($l === 19) {
				$format = '!d/m/Y H:i:s';
			}
		}
		elseif ($b === '/') {
			// YYYY/MM/DD
			if ($l === 10) {
				$format = '!Y/m/d';
			}
			// YYYY/MM/DD HH:MM
			elseif ($l === 16) {
				$format = '!Y/m/d H:i';
			}
			// YYYY/MM/DD HH:MM:SS
			elseif ($l === 19) {
				$format = '!Y/m/d H:i:s';
			}
		}
		elseif ($b === '-') {
			// YYYY-MM-DD HH:MM:SS
			if ($l === 19) {
				$format = '!Y-m-d H:i:s';
			}
			// YYYY-MM-DD HH:MM
			elseif ($l === 16) {
				$format = '!Y-m-d H:i';
			}
			// YYYY-MM-DD
			elseif ($l === 10) {
				$format = '!Y-m-d';
			}
		}
		elseif (ctype_digit($value)) {
			// YYYYMMDD
			if ($l === 8 && preg_match('!^20\d{2}[01]\d[0123]\d$!', $value)) {
				$format = '!Ymd';
			}
			else {
				$format = 'U';
			}
		}

		if (null !== $format) {
			if ($class === Date::class) {
				$date = Date::createFromFormat($format, $value);
			}
			else {
				$date = \DateTime::createFromFormat($format, $value);
			}
		}

		if (!$date) {
			$e = new ValidationException('Format de date invalide (merci d\'utiliser le format JJ/MM/AAAA) : ' . $value);
			ErrorManager::reportExceptionSilent($e); // FIXME: don't report invalid dates
			throw $e;
		}

		$y = $date->format('Y');
		if ($y < 1900 || $y > 2100) {
			throw new ValidationException(sprintf('Date invalide (%s) : doit être entre 1900 et 2100', $value));
		}

		return $date;
	}

	protected function filterUserValue(string $type, $value, string $key)
	{
		if ($type === 'date'
			|| $type === Date::class
			|| $type === \DateTimeInterface::class
			|| $type === \DateTime::class)
		{
			return self::filterUserDateValue($value, $type);
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

		// We are doing selfcheck here before sending the before event
		if ($selfcheck) {
			$this->selfCheck();
		}

		$new = $this->exists() ? false : true;
		$modified = $this->isModified();
		$entity = $this;
		$params = compact('entity', 'new', 'modified');

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
			$params['modified_properties'] = $this->getModifiedProperties();
		}

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
