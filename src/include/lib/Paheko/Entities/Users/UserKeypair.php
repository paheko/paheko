<?php

namespace Paheko\Entities\Users;

use Paheko\Entity;

class UserKeypair extends Entity
{
	const TABLE = 'users_keys';

	protected ?int $id;
	protected int $id_user;
	protected ?string $public;

	/**
	 * This is not the real private key, it's encrypted using the users password
	 */
	protected ?string $private;

	/**
	 * This is the actual decrypted private key
	 */
	protected ?string $_private_key;

	public function __destruct()
	{
		sodium_memzero($this->_private_key);
	}

	public function decrypt(
		#[\SensitiveParameter]
		string $password
	): bool
	{
		$pkey = Vaults::decryptWithPassword($password, $this->private);

		if (null === $pkey) {
			return false;
		}

		$this->_private_key = $pkey;
		return true;
	}

	public function encrypt(
		#[\SensitiveParameter]
		string $password
	): void
	{
		if (!isset($this->public, $this->private)) {
			$keypair = sodium_crypto_box_keypair();
			$this->set('public', sodium_crypto_box_publickey($keypair));
			$this->_private_key = sodium_crypto_box_secretkey($keypair);
			sodium_memzero($keypair);
		}

		$encrypted_private_key = Vaults::encryptWithPassword($password, $this->_private_key);
		sodium_memzero($password);

		$this->set('private', $encrypted_private_key);
	}

	/**
	 * This will allow to change the user password while still keeping the same public key,
	 * only the stored private key is decrypted and re-encrypted using the new password.
	 */
	public function changePassword(
		#[\SensitiveParameter]
		bool $old_password,
		#[\SensitiveParameter]
		string $new_password
	): bool
	{
		$ok = $this->decrypt($old_password);

		if (!$ok) {
			return false;
		}

		$this->encrypt($new_password);

		return true;
	}

	public function getPrivateKey(): string
	{
		if (!isset($this->_private_key)) {
			throw new \LogicException('The user private key is still encrypted');
		}

		return $this->_private_key;
	}

	public function getKeypair(): string
	{
		return sodium_crypto_box_keypair_from_secretkey_and_publickey($this->getPrivateKey(), $this->public);
	}
}
