<?php
namespace Paheko;

use Paheko\UserTemplate\Modules;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$ok = qg('ok');

$parent_name = $user->getParentName();
$children = $user->listChildren();

$user = Session::getInstance()->user();

$variables = compact('user', 'parent_name', 'children', 'ok');
$tpl->assign('snippets', Modules::snippetsAsString(Modules::SNIPPET_MY_DETAILS, $variables));

$tpl->assign($variables);

$tpl->display('me/index.tpl');
