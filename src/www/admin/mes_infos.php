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

            $session->editUser($data);

            Utils::redirect('/admin/');
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
