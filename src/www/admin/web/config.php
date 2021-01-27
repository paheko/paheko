<?php
namespace Garradin;

use Garradin\Web\Skeleton;

require_once __DIR__ . '/_inc.php';

$config = Config::getInstance();

if (f('desactiver_site') && $form->check('config_site'))
{
	$config->set('desactiver_site', true);
	$config->save();
	Utils::redirect(Utils::getSelfURI());
}
elseif (f('activer_site') && $form->check('config_site'))
{
	$config->set('desactiver_site', false);
	$config->save();
	Utils::redirect(Utils::getSelfURI());
}

$form->runIf('reset', function () {
	Skeleton::resetSelected(f('select'));
}, 'squelettes', Utils::getSelfURI('reset_ok'));

$form->runIf('upload', function () {
	Skeleton::upload(f('name'), 'file');
}, 'skel_upload', Utils::getSelfURI());

if (qg('edit')) {
	$source = trim(qg('edit'));
	$csrf_key = 'edit_skel_' . md5($source);

	$form->runIf('save', function () use ($source) {
		$tpl = new Skeleton($source);
		$tpl->edit(f('content'));
		$fullscreen = null !== qg('fullscreen') ? '#fullscreen' : '';
		Utils::redirect(Utils::getSelfURI(sprintf('edit=%s&ok%s', rawurlencode($source), $fullscreen)));
	}, $csrf_key);

	$tpl->assign('edit', ['file' => $source, 'content' => (new Skeleton($source))->raw()]);
	$tpl->assign('csrf_key', $csrf_key);
}

$tpl->assign('sources', Skeleton::list());

$tpl->assign('reset_ok', qg('reset_ok') !== null);
$tpl->assign('ok', qg('ok') !== null);

$tpl->display('web/config.tpl');
