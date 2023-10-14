<?php

namespace Paheko\Email;

use Paheko\Entities\Users\User;
use Paheko\Email\Emails;
use Paheko\Users\DynamicFields;
use Paheko\Template;
use Paheko\Utils;

use const Paheko\{ADMIN_URL};

class Templates
{
	static protected function send($to, string $template, array $variables = [])
	{
		$tpl = Template::getInstance();
		$tpl->assign($variables);
		$tpl->setEscapeType('disable');
		$body = trim($tpl->fetch('emails/' . $template));
		$subject = $tpl->getTemplateVars('subject');

		if (!$subject) {
			throw new \LogicException('Template did not define a subject');
		}

		Emails::queue(Emails::CONTEXT_SYSTEM, (array)$to, null, $subject, $body);
	}

	static public function loginChanged(User $user): void
	{
		$login_field = DynamicFields::getLoginField();
		self::send($user, 'login_changed.tpl', ['new_login' => $user->$login_field]);
	}

	static public function passwordRecovery(string $email, string $recovery_url, ?string $pgp_key): void
	{
		self::send([$email => compact('pgp_key')], 'password_recovery.tpl', compact('recovery_url'));
	}

	static public function passwordChanged(User $user): void
	{
		$ip = Utils::getIP();
		$login_field = DynamicFields::getLoginField();
		$login = $user->$login_field;
		self::send($user, 'password_changed.tpl', compact('ip', 'login'));
	}

	static public function verifyAddress(string $email, string $verify_url): void
	{
		self::send($email, 'verify_email.tpl', compact('verify_url'));
	}
}
