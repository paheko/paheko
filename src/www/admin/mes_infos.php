<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$champs = $config->get('champs_membres');

if (f('save'))
{
    $form->check('edit_me', $champs->getValidationRules('user_edit'));

    if (!$form->hasErrors())
    {
        try {
            $data = [];

            foreach ($champs->getAll() as $key=>$c)
            {
                if (!empty($c->editable))
                {
                    $data[$key] = f($key);
                }
            }

            if (isset($data[$config->get('champ_identifiant')]) && !trim($data[$config->get('champ_identifiant')]) && $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)) {
                throw new UserException("Le champ identifiant ne peut être vide pour un administrateur, sinon vous ne pourriez plus vous connecter.");
            }

            $session->editUser($data);

            Utils::redirect(ADMIN_URL);
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('champs', $champs->getAll());

$tpl->assign('membre', $session->getUser());

$tpl->display('admin/mes_infos.tpl');
