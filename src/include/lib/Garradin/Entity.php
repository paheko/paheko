<?php

namespace Garradin;

use Garradin\Form;
use KD2\DB\AbstractEntity;
use KD2\DB\Date;

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

		return $this->import($source);
	}

	static public function filterUserDateValue(?string $value): ?\DateTime
	{
		$value = trim((string) $value);

		if (!$value) {
			return null;
		}

		if (preg_match('!^\d{2}/\d{2}/\d{2}$!', $value)) {
			return Date::createFromFormat('d/m/y', $value);
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
		elseif (preg_match('!^\d{4}-\d{2}-\d{2}$!', $value)) {
			return Date::createFromFormat('Y-m-d', $value);
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

			return self::filterUserDateValue($value);
		}
		elseif ($type == 'DateTime' && is_string($value)) {
			if (preg_match('!^\d{2}/\d{2}/\d{4}\s\d{1,2}:\d{2}$!', $value)) {
				return \DateTime::createFromFormat('d/m/Y H:i', $value);
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
		$name = str_replace('Garradin\Entities\\', '', $name);
		$name = 'entity.' . $name . '.save';

		// We are doing selfcheck here before sending the before event
		if ($selfcheck) {
			$this->selfCheck();
		}

		$new = $this->exists() ? false : true;
		$modified = $this->isModified();

		// Specific entity signal
		if (Plugin::fireSignal($name . '.before', ['entity' => $this, 'new' => $new])) {
			return true;
		}

		// Generic entity signal
		if (Plugin::fireSignal('entity.save.before', ['entity' => $this, 'new' => $new])) {
			return true;
		}

		$return = parent::save(false);

		// Log creation/edit, but don't record stuff that doesn't change anything
		if ($this::NAME && ($new || $modified)) {
			$type = str_replace('Garradin\Entities\\', '', get_class($this));
			Log::add($new ? Log::CREATE : Log::EDIT, ['entity' => $type, 'id' => $this->id()]);
		}

		Plugin::fireSignal($name . '.after', ['entity' => $this, 'success' => $return, 'new' => $new]);

		Plugin::fireSignal('entity.save.after', ['entity' => $this, 'success' => $return, 'new' => $new]);

		return $return;
	}

	public function delete(): bool
	{
		$name = get_class($this);
		$name = str_replace('Garradin\Entities\\', '', $name);
		$name = 'entity.' . $name . '.delete';

		$id = $this->id();

		if (Plugin::fireSignal($name . '.before', ['entity' => $this, 'id' => $id])) {
			return true;
		}

		// Generic entity signal
		if (Plugin::fireSignal('entity.delete.before', ['entity' => $this, 'id' => $id])) {
			return true;
		}

		$return = parent::delete();

		if ($this::NAME) {
			$type = str_replace('Garradin\Entities\\', '', get_class($this));
			Log::add(Log::DELETE, ['entity' => $type, 'id' => $id]);
		}

		Plugin::fireSignal($name . '.after', ['entity' => $this, 'success' => $return, 'id' => $id]);
		Plugin::fireSignal('entity.delete.after', ['entity' => $this, 'success' => $return, 'id' => $id]);

		return $return;
	}
}
