<?php

namespace Garradin\Files\WebDAV;

use Garradin\Users\Session as UserSession;

use Garradin\DB;

use const Garradin\{WWW_URL};

class Session extends UserSession
{
	static protected $_instance = null;

	// Use a different session name so that someone cannot access the admin
	// with a cookie from WebDAV/app
	protected $cookie_name = 'gdinw';

	/**
	 * Create a temporary app token for an external service session (eg. NextCloud)
	 */
	public function generateAppToken(): string
	{
		$token = hash('sha256', random_bytes(16));

		$expiry = time() + 30*60; // 30 minutes
		DB::getInstance()->preparedQuery('REPLACE INTO users_sessions (selector, hash, id_user, expiry)
			VALUES (?, ?, ?, ?);',
			'tok_' . $token, 'waiting', null, $expiry);

		return $token;
	}

	/**
	 * Validate the temporary token once the user has logged-in
	 */
	public function validateAppToken(string $token): bool
	{
		if (!ctype_alnum($token) || strlen($token) > 64) {
			return false;
		}

		$token = $this->getRememberMeSelector('tok_' . $token);

		if (!$token || $token->hash != 'waiting') {
			return false;
		}

		$user = $this->getUser();

		if (!$user) {
			throw new \LogicException('Cannot create a token if the user is not logged-in');
		}

		DB::getInstance()->preparedQuery('UPDATE users_sessions SET hash = \'ok\', id_user = ? WHERE selector = ?;',
			$user->id, $token->selector);

		return true;
	}

	/**
	 * Verify temporary app token and create a session,
	 * this is similar to "remember me" sessions but without cookies
	 */
	public function verifyAppToken(string $token): ?array
	{
		if (!ctype_alnum($token) || strlen($token) > 64) {
			return null;
		}

		$token = $this->getRememberMeSelector('tok_' . $token);

		if (!$token || $token->hash != 'ok') {
			return null;
		}

		// Delete temporary token
		#$this->deleteRememberMeSelector($token->selector);

		if ($token->expiry < time()) {
			return null;
		}

		// Create a real session, not too long
		$selector = $this->createSelectorValues($token->user_id, $token->user_password, '+1 month');
		$this->storeRememberMeSelector($selector->selector, $selector->hash, $selector->expiry, $token->user_id);

		$login = $selector->selector;
		$password = $selector->verifier;

		return compact('login', 'password');
	}


	public function createAppCredentials(): \stdClass
	{
		if (!$this->isLogged()) {
			throw new \LogicException('User is not logged');
		}

		$user = $this->getUser();
		$selector = $this->createSelectorValues($user->id, $user->password);
		$this->storeRememberMeSelector($selector->selector, $selector->hash, $selector->expiry, $user->id);

		$login = $selector->selector;
		$password = $selector->verifier;
		$redirect = sprintf(NextCloud::AUTH_REDIRECT_URL, WWW_URL, $login, $password);

		return (object) compact('login', 'password', 'redirect');
	}

	public function checkAppCredentials(string $login, string $password): ?User
	{
		$selector = $this->getRememberMeSelector($login);

		if (!$selector) {
			return null;
		}

		if (!$this->checkRememberMeSelector($selector, $password)) {
			$this->deleteRememberMeSelector($selector->selector);
			return null;
		}

		$this->_user = Users::get($selector->user_id);

		if (!$this->_user) {
			return null;
		}

		$this->user = $selector->user_id;

		return $this->_user;
	}
}
