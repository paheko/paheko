<?php
namespace Paheko;

use Paheko\Accounting\Projects;
use Paheko\Entities\Accounting\Project;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

if ($id = (int)qg('id')) {
	$project = Projects::get($id);
}
else {
	$project = new Project;
}

$csrf_key = 'project_edit';

$form->runIf('save', function () use ($project) {
	$project->importForm();
	$project->save();
}, $csrf_key, '!acc/projects/');

$tpl->assign(compact('csrf_key', 'project'));

$tpl->display('acc/projects/edit.tpl');
