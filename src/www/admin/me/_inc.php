<?php

namespace Paheko;

use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$user = Session::getInstance()->getUser();

if (!$user->exists()) {
	throw new UserException('Only existing users can change their info');
}
