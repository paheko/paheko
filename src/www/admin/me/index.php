<?php
namespace Garradin;

use Garradin\UserTemplate\Modules;

require_once __DIR__ . '/_inc.php';

$ok = qg('ok');

$tpl->assign(compact('user', 'ok'));

$variables = compact('user');
$tpl->assign('snippets', Modules::snippetsAsString(Modules::SNIPPET_USER, $variables));

$tpl->display('me/index.tpl');
