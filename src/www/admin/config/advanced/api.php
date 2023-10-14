<?php

namespace Paheko;

use Paheko\API_Credentials;
use Paheko\Entities\API_Credentials AS API_Entity;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'api_edit';
$secret = null;

$form->runIf('add', function () {
	API_Credentials::create();
}, $csrf_key, Utils::getSelfURI());

$form->runIf('delete', function () {
	API_Credentials::delete((int)f('id'));
}, $csrf_key, Utils::getSelfURI());

$list = API_Credentials::list();
$default_key = API_Credentials::generateKey();
$secret = API_Credentials::generateSecret();
$access_levels = API_Entity::ACCESS_LEVELS;

$tpl->assign('website', WEBSITE);
$tpl->assign(compact('list', 'csrf_key', 'default_key', 'secret', 'access_levels'));

$tpl->display('config/advanced/api.tpl');
