<?php
namespace Garradin;

use Garradin\Web\Skeleton;

require_once __DIR__ . '/_inc.php';

$config = Config::getInstance();

$session->requireAccess($session::SECTION_WEB, $session::ACCESS_ADMIN);

if (f('disable_site') && $form->check('config_site'))
{
	$config->set('site_disabled', true);
	$config->save();
	Utils::redirect(Utils::getSelfURI());
}
elseif (f('enable_site') && $form->check('config_site'))
{
	$config->set('site_disabled', false);
	$config->save();
	Utils::redirect(Utils::getSelfURI());
}

$form->runIf('reset', function () {
	if (!f('select')) {
		return;
	}

	Skeleton::resetSelected(f('select'));
}, 'squelettes', Utils::getSelfURI('reset_ok'));

if (qg('edit')) {
	$source = trim(qg('edit'));
	$csrf_key = 'edit_skel_' . md5($source);

	$form->runIf('save', function () use ($source) {
		$tpl = new Skeleton($source);
		$tpl->edit(f('content'));
		$fullscreen = null !== qg('fullscreen') ? '#fullscreen' : '';
		Utils::redirect(Utils::getSelfURI(sprintf('edit=%s&ok%s', rawurlencode($source), $fullscreen)));
	}, $csrf_key);

	try {
		$skel = new Skeleton($source);
	}
	catch (\InvalidArgumentException $e) {
		throw new UserException('Nom de squelette invalide');
	}

	$tpl->assign('edit', ['file' => $source, 'content' => $skel->raw()]);
	$tpl->assign('csrf_key', $csrf_key);
}

$tpl->assign('sources', Skeleton::list());

$tpl->assign('reset_ok', qg('reset_ok') !== null);
$tpl->assign('ok', qg('ok') !== null);

$tpl->display('web/config.tpl');
