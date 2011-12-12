<?php

define('GARRADIN_LOGIN_PROCESS', true);
require_once __DIR__ . '/_inc.php';

$membres->logout();
utils::redirect('/');

?>