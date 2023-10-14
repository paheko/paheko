<?php
namespace Paheko;

const LOGIN_PROCESS = true;
require_once __DIR__ . '/_inc.php';

$session->logout(qg('all') !== null);
Utils::redirect('!login.php?logout');
