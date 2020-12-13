<?php

namespace Garradin;

use Garradin\Web;
use Garradin\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

$id = (int) qg('id');
$page = Web::get($id);

if (!$page) {
    throw new UserException('Page inconnue');
}

$csrf_key = 'edit_' . $page->id();

$form->runIf('save', function ($page) {
    $page->importForm();
    $page->save();
}, $csrf_key, Utils::getSelfURI());

$tpl->assign(compact('page', 'csrf_key'));

$tpl->display('web/edit.tpl');
