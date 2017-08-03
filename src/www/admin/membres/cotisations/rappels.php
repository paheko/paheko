<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$membre = $membres->get($id);

if (!$membre)
{
	throw new UserException("Ce membre n'existe pas.");
}

$re = new Rappels_Envoyes;
$cm = new Membres\Cotisations;

if (f('save'))
{
	$medias = implode(',', [$re::MEDIA_EMAIL, $re::MEDIA_COURRIER, $re::MEDIA_TELEPHONE, $re::MEDIA_AUTRE]);

	$form->check('add_rappel_' . $membre->id, [
		'id_cotisation' => 'numeric|required',
		'media'         => 'numeric|required|in:' . $medias,
		'date'          => 'required|date_format:Y-m-d'
	]);

	if (!$form->hasErrors())
	{
		try {
			$re->add([
				'id_rappel'     => NULL,
				'id_cotisation'	=> f('id_cotisation'),
				'id_membre'		=> $membre->id,
				'media'			=> f('media'),
				'date'			=> f('date'),
			]);

			Utils::redirect('/admin/membres/cotisations/rappels.php?id=' . $membre->id . '&ok');
		}
		catch (UserException $e)
		{
			$form->addError($e->getMessage());
		}
	}
}

$tpl->assign('ok', null !== qg('ok'));
$tpl->assign('membre', $membre);
$tpl->assign('cotisations', $cm->listSubscriptionsForMember($membre->id));
$tpl->assign('default_date', date('Y-m-d'));
$tpl->assign('rappels', $re->listForMember($membre->id));
$tpl->assign('rappels_envoyes', $re);

$tpl->display('admin/membres/cotisations/rappels.tpl');
