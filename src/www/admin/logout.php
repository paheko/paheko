<?php
namespace Garradin;

const LOGIN_PROCESS = true;
require_once __DIR__ . '/_inc.php';

$membres->logout();
Utils::redirect('/');

?>