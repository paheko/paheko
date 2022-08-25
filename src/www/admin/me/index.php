<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$ok = qg('ok');

$tpl->assign(compact('user', 'ok'));

$tpl->display('me/index.tpl');
