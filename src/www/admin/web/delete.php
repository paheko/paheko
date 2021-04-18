<?php
namespace Garradin;

use Garradin\Web\Web;
use Garradin\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$page = Web::get(qg('p'));

if (!$page) {
	throw new UserException('Page inconnue');
}

$csrf_key = 'web_delete_' . $page->id();

$form->runIf('delete', function () use ($page) {
	$page->delete();
}, $csrf_key, ADMIN_URL . 'web/?parent=' . $page->parent);

$tpl->assign(compact('page', 'csrf_key'));
$tpl->assign('title', $page->type == Page::TYPE_CATEGORY ? 'Supprimer une catégorie' : 'Supprimer une page');
$tpl->assign('alert', $page->type == Page::TYPE_CATEGORY ? 'Attention ceci supprimera toutes les sous-catégories, pages et fichiers dans cette catégorie.' : 'Attention ceci supprimera tous les fichiers liés dans cette page.');

$tpl->display('web/delete.tpl');
