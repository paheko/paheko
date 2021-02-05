<?php

namespace Garradin;

use Garradin\Web\Web;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$page = Web::get((int)qg('id'));

$tpl->assign('content', $page->preview(f('content')));

$tpl->assign('custom_css', ['!web/css.php']);

$tpl->display('web/_preview.tpl');
