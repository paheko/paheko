<?php
namespace Garradin;

use Garradin\UserTemplate\UserForms;

require_once __DIR__ . '/../_inc.php';

UserForms::refresh();

$list = UserForms::list();

$tpl->assign(compact('list'));

$tpl->display('config/forms/index.tpl');
