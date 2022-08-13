<?php
namespace Garradin;

use Garradin\Users\Session;

require_once __DIR__ . '/../_inc.php';

$user = Session::getInstance()->getUser();

$ok = qg('ok');

$tpl->assign(compact('user', 'ok'));

$tpl->display('me/index.tpl');
