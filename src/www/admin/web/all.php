<?php

namespace Paheko;

use Paheko\UserTemplate\Modules;
use Paheko\Web\Web;
use Paheko\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

$list = Web::getAllList();

$list->loadFromQueryString();

$can_edit = $session->canAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$tpl->assign('custom_css', ['web.css']);

$tpl->assign(compact('list', 'can_edit'));

$tpl->display('web/all.tpl');
