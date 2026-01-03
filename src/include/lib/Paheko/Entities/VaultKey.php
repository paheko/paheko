<?php

namespace Paheko\Entities;

use Paheko\Entities\Users\UserKeypair;
use Paheko\Entity;
use Paheko\Vaults;

use const Paheko\LOCAL_SECRET_KEY;

class VaultKey extends Entity
{
	const TABLE = 'vaults_keys';

	protected ?int $id;
	protected int $id_vault;
	protected ?int $id_user = null;
	protected ?int $id_plugin = null;
	protected string $key;

	/**
	 * This is the actual decrypted private key
	 */
	protected string $_private_key;

	public function __destruct()
	{
		sodium_memzero($this->_private_key);
	}

	/**
	 * Store data inside the vault using the supplied password
	 */
	public function storeWithPassword(
		#[\SensitiveParameter]
		string $data,
		#[\SensitiveParameter]
		?string $password = null
	): void
	{
		// If no password has been supplied, use the secret key
		$password ??= LOCAL_SECRET_KEY;

		if (!isset($this->key)) {
			$this->encryptWithPassword($password);
		}
		else {
			$this->decryptWithPassword($password);
		}

		$vault = $this->vault();
		$vault->encrypt($this, $data);
		$vault->save();
		$this->set('id_vault', $vault->id());
		$this->save();
	}

	protected function generatePrivateKey(): string
	{
		$keypair = sodium_crypto_box_keypair();
		$private_key = sodium_crypto_box_secretkey($keypair);
		sodium_memzero($keypair);
		return $private_key;
	}

	public function getPrivateKey(): string
	{
		if (!isset($this->_private_key)) {
			throw new \LogicException('This key is still encrypted');
		}

		return $this->_private_key;
	}

	public function encryptWithPassword(
		#[\SensitiveParameter]
		string $password
	): void
	{
		$this->_private_key ??= $this->generatePrivateKey();

		$key = Vaults::encryptWithPassword($password, $this->_private_key);

		$this->set('key', $key);
	}

	public function decryptWithPassword(
		#[\SensitiveParameter]
		string $password
	): bool
	{
		$private_key = Vaults::decryptWithPassword($password, $this->key);

		if ($private_key === null) {
			return false;
		}

		$this->_private_key = $private_key;
		return true;
	}

	public function encryptForUser(UserKeypair $keypair)
	{
		$this->_private_key ??= $this->generatePrivateKey();

		$encrypted_key = sodium_crypto_box_seal($this->_private_key, $keypair->getPublicKey());
		$this->set('key', sodium_bin2hex($encrypted_key));
		sodium_memzero($encrypted_key);
		$this->set('id_user', $keypair->id_user);
	}

	public function decryptForUser(UserKeypair $keypair)
	{
		if ($this->id_user !== $keypair->id_user) {
			throw new \LogicException('Trying to decrypt with the wrong keypair');
		}

		$key = sodium_hex2bin($this->key);
		$private_key = sodium_crypto_box_seal_open($key, $keypair->getKeypair());

		if ($private_key === false) {
			return false;
		}

		$this->_private_key = $private_key;
		sodium_memzero($key);
		return true;
	}

	public function vault(): Vault
	{
		if (!isset($this->id_vault)) {
			return new Vault;
		}
		else {
			return Vaults::get($this->id_vault);
		}
	}
}
