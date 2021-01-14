<?php

namespace Garradin;

use Garradin\Web\Render\Skriv;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, Membres::DROIT_ECRITURE);

$tpl->assign('content', Skriv::render(null, (string) f('content'), ['prefix' => '#']));

$tpl->display('web/_preview.tpl');
