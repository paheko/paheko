<?php
declare(strict_types=1);

namespace Paheko\Entities\Files;

use Paheko\Files\Files;
use Paheko\Entity;

use DateTime;

use const Paheko\{WWW_URL, SECRET_KEY};

class Share extends Entity
{
	const TABLE = 'files_shares';

	const DOWNLOAD = 0;
	const VIEW = 1;
	const EDIT = 2;

	const OPTIONS = [
		self::DOWNLOAD => 'Télécharger le fichier seulement',
		self::VIEW => 'Prévisualiser et télécharger',
		self::EDIT => 'Modifier et télécharger',
	];

	const TTL_OPTIONS = [
		1         => 'Une heure',
		24        => 'Un jour',
		24*7      => 'Une semaine',
		24*31     => 'Un mois',
		24*90     => '3 mois',
		24*180    => '6 mois',
		24*365    => 'Un an',
		0         => 'Infinie',
	];

	const DEFAULT_TTL = 24*7;

	protected ?int $id = null;
	protected int $id_file;
	/**
	 * id_user can be NULL if using a SSO (logged-in user doesn't necessarily exist in local DB)
	 */
	protected ?int $id_user;
	protected DateTime $created;
	protected string $hash_id;
	protected int $option;
	protected ?DateTime $expiry;
	protected ?string $password;


	public function selfCheck(): void
	{
		$this->assert(isset($this->option) && array_key_exists($this->option, self::OPTIONS), 'Unknown sharing option');
	}

	public function file(): ?File
	{
		return Files::getById($this->id_file);
	}

	public function url(): string
	{
		return WWW_URL . 's/' . $this->hash_id;
	}

	public function download_url(?File $file = null): string
	{
		$file ??= $this->file();
		return WWW_URL . 's/' . $this->hash_id . '/' . rawurlencode($file->name);
	}

	public function verifyPassword(string $password): bool
	{
		return $this->password && password_verify(trim($password), $this->password);
	}

	public function verifyToken(string $token): bool
	{
		$random_hash = strtok($token, ':') ?: '';
		$hmac = strtok('') ?: '';

		return hash_equals($token, $this->generateToken($random_hash));
	}

	public function generateToken(?string $hash = null): string
	{
		$hash ??= sha1(random_bytes(16));
		return $hash . ':' . hash_hmac('sha256', $hash, SECRET_KEY . $this->password . $this->id());
	}

	public function importForm(?array $source = null)
	{
		$source ??= $_POST;

	 	if (isset($source['ttl'])) {
	 		$this->set('expiry', !empty($source['ttl']) ? new DateTime(sprintf('+%d hours', $source['ttl'])) : null);
	 	}

	 	if (array_key_exists('password', $source)) {
	 		$this->set('password', $source['password'] ? password_hash(trim($source['password']), \PASSWORD_DEFAULT) : null);
	 		unset($source['password']);
	 	}

	 	parent::import($source);
	}
}
