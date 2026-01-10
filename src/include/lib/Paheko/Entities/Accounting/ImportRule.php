<?php

namespace Paheko\Entities\Accounting;

use Paheko\Entity;

class ImportRule extends Entity
{
	const TABLE = 'acc_import_rules';

	protected ?int $id;
	protected ?string $label = null;
	protected bool $regexp = false;

	protected ?string $match_file_name = null;
	protected ?string $match_account = null;
	protected ?string $match_label = null;
	protected ?string $match_date = null;
	protected ?int $min_amount = null;
	protected ?int $max_amount = null;

	protected ?string $target_account = null;
	protected ?string $new_label = null;
	protected ?string $new_reference = null;
	protected ?string $new_payment_ref = null;

	const REGEXP_DELIMITER = '@';
	const REGEXP_OPTIONS = 'Ui';

	static public function getRegexp(string $match)
	{
		$regexp = str_replace(self::REGEXP_DELIMITER, '\\' . self::REGEXP_DELIMITER, $match);
		$regexp = self::REGEXP_DELIMITER . $regexp . self::REGEXP_DELIMITER . self::REGEXP_OPTIONS;
		return $regexp;
	}

	protected function validateRegexp(string $name, string $label): void
	{
		if (!isset($this->$name)) {
			return;
		}

		$regexp = self::getRegexp($this->$name);

		try {
			$r = preg_match($regexp, '');
		}
		catch (\Throwable $e) {
			$msg = preg_replace('/^Warning:.*?:\s*/', '', $e->getMessage());
			$this->assert(false, sprintf('%s : regexp invalide (%s)', $label, $msg));
		}
	}

	public function selfCheck(): void
	{
		if ($this->regexp) {
			$this->validateRegexp('match_file_name', 'Nom du fichier importé');
			$this->validateRegexp('match_account', 'Compte utilisé pour l\'import');
			$this->validateRegexp('match_label', 'Libellé de l\'opération importée');
			$this->validateRegexp('match_date', 'Date de l\'opération importée');
		}
	}
}
