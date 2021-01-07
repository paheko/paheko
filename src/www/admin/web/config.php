<?php
namespace Garradin;

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
	foreach (f('select') as $source)
	{
		if (!Squelette::resetSource($source))
		{
			throw new UserException('Impossible de réinitialiser le squelette.');
		}
	}
}, 'squelettes', Utils::getSelfURI('reset_ok'));


if (qg('edit')) {
	$source = Squelette::getSource(qg('edit'));

	if (null === $source)
	{
		throw new UserException("Ce squelette n'existe pas.");
	}

	$csrf_key = 'edit_skel_' . md5(qg('edit'));

	$form->runIf('save', function () {
		if (Squelette::editSource(qg('edit'), f('content')))
		{
			$fullscreen = null !== qg('fullscreen') ? '#fullscreen' : '';
			Utils::redirect(Utils::getSelfURI(sprintf('edit=%s&ok%s', rawurlencode(qg('edit')), $fullscreen)));
		}
		else
		{
			throw new UserException("Impossible d'enregistrer le squelette.");
		}
	}, $csrf_key);

	$tpl->assign('edit', ['file' => trim(qg('edit')), 'content' => $source]);
	$tpl->assign('csrf_key', $csrf_key);
}

$tpl->assign('sources', Squelette::listSources());

$tpl->assign('reset_ok', qg('reset_ok') !== null);
$tpl->assign('ok', qg('ok') !== null);

$tpl->display('web/config.tpl');
