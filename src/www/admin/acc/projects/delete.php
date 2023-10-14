<?php
namespace Paheko;

use Paheko\Accounting\Projects;
use Paheko\Entities\Accounting\Project;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$project = Projects::get((int)qg('id'));

if (!$project) {
	throw new UserException('Projet introuvable');
}

$csrf_key = 'project_delete';

$form->runIf('delete', function () use ($project) {
	$project->delete();
}, $csrf_key, '!acc/projects/');

$tpl->assign(compact('csrf_key', 'project'));

$tpl->display('acc/projects/delete.tpl');
