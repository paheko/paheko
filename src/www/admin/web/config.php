<?php
namespace Garradin;

use Garradin\Web\Template;

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
	Template::resetSelected(f('select'));
}, 'squelettes', Utils::getSelfURI('reset_ok'));

if (qg('edit')) {
	$source = trim(qg('edit'));
	$csrf_key = 'edit_skel_' . md5($source);

	$form->runIf('save', function () use ($source) {
		$tpl = new Template($source);
		$tpl->edit(f('content'));
		$fullscreen = null !== qg('fullscreen') ? '#fullscreen' : '';
		Utils::redirect(Utils::getSelfURI(sprintf('edit=%s&ok%s', rawurlencode($source), $fullscreen)));
	}, $csrf_key);

	$tpl->assign('edit', ['file' => $source, 'content' => (new Template($source))->raw()]);
	$tpl->assign('csrf_key', $csrf_key);
}

$tpl->assign('sources', Template::list());

$tpl->assign('reset_ok', qg('reset_ok') !== null);
$tpl->assign('ok', qg('ok') !== null);

$tpl->display('web/config.tpl');
