<?php

namespace Paheko\Files\WebDAV;

use Paheko\DB;
use Paheko\Users\Users;
use Paheko\Entities\Users\User;

use Paheko\Users\Session as UserSession;

use const Paheko\{WWW_URL};

class Session extends UserSession
{
	static protected $_instance = null;

	// Use a different session name so that someone cannot access the admin
	// with a cookie from WebDAV/app
	protected $cookie_name = 'pkow';

	/**
	 * Create a temporary app token for an external service session (eg. NextCloud)
	 */
	public function generateAppToken(): string
	{
		$token = hash('sha256', random_bytes(10));

		$expiry = time() + 30*60; // 30 minutes
		$this->storeRememberMeSelector('tok_' . $token, 'waiting', $expiry, null);

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

		DB::getInstance()->preparedQuery('UPDATE users_sessions
			SET hash = \'ok\', id_user = ?, expiry = expiry + 30*60
			WHERE selector = ?;',
			$user->id, $this->cookie_name . '_' . $token->selector);

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
		$this->deleteRememberMeSelector($token->selector);

		if ($token->expiry < time()) {
			return null;
		}

		$new_token = base_convert(sha1(random_bytes(10)), 16, 36);
		$selector = 'app_' . substr($new_token, 0, 16);
		$selector = $this->createSelectorValues($token->user_id, $token->user_password, null, $selector);
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
		$token = base_convert(sha1(random_bytes(10)), 16, 36);
		$selector = 'app_' . substr($token, 0, 16);
		$selector = $this->createSelectorValues($user->id, $user->password, null, $selector);
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
