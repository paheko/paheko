<?php
namespace Garradin;

use Garradin\UserTemplate\UserForms;
use Garradin\Entities\UserForm;

require_once __DIR__ . '/_inc.php';

$ok = qg('ok');

$tpl->assign(compact('user', 'ok'));

$variables = compact('user');
$tpl->assign('snippets', UserForms::getSnippets(UserForm::SNIPPET_USER, $variables));

$tpl->display('me/index.tpl');
