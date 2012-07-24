<?php

require_once GARRADIN_ROOT . '/include/class.compta_comptes.php';

class Garradin_Compta_Comptes_Bancaires extends Garradin_Compta_Comptes
{
    const NUMERO_PARENT_COMPTES = 512;

    public function add($data)
    {
        $db = Garradin_DB::getInstance();

        $data['parent'] = self::NUMERO_PARENT_COMPTES;
        $data['id'] = null;

        $new_id = parent::add($data);

        $db = Garradin_DB::getInstance();
        $db->simpleInsert('compta_comptes_bancaires', array(
            'id'        =>  $new_id,
            'banque'    =>  $data['banque'],
            'iban'      =>  $data['iban'],
            'bic'       =>  $data['bic'],
        ));

        return $new_id;
    }

    public function edit($id, $data)
    {
        $db = Garradin_DB::getInstance();

        if ($db->simpleQuerySingle('SELECT 1 FROM compta_comptes_bancaires WHERE id = ?;', false, $id))
        {
            throw new UserException('Ce compte n\'est pas un compte bancaire.');
        }

        $result = parent::edit($id, $data);

        if (!$result)
        {
            return $result;
        }

        $db->simpleUpdate('compta_comptes_bancaires', array(
            'banque'    =>  $data['banque'],
            'iban'      =>  $data['iban'],
            'bic'       =>  $data['bic'],
        ), 'id = \''.$db->escapeString(trim($id)).'\'');

        return true;
    }

    public function delete($id)
    {
        $return = parent::delete($id);

        $db = Garradin_DB::getInstance();
        $db->simpleExec('DELETE FROM compta_comptes_bancaires WHERE id = ?;', trim($id));

        return $return;
    }

    public function get($id)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleQuerySingle('SELECT * FROM compta_comptes AS c
            INNER JOIN compta_comptes_bancaires AS cc
            ON c.id = cc.id
            WHERE c.id = ?;', true, $id);
    }

    public function getList($parent = false)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleStatementFetchAssocKey('SELECT c.id AS id, * FROM compta_comptes AS c
            INNER JOIN compta_comptes_bancaires AS cc ON c.id = cc.id
            WHERE c.parent = '.self::NUMERO_PARENT_COMPTES.' ORDER BY c.id;');
    }

    protected function _checkFields(&$data, $ignored = null)
    {
        parent::_checkFields($data);

        if (empty($data['banque']) || !trim($data['banque']))
        {
            throw new UserException('Le nom de la banque ne peut rester vide.');
        }

        if (empty($data['bic']))
        {
            $data['bic'] = '';
        }
        else
        {
            $data['bic'] = trim(strtoupper($data['bic']));
            $data['bic'] = preg_replace('![^\dA-Z]!', '', $data['bic']);

            if (!utils::checkBIC($data['bic']))
            {
                throw new UserException('Code BIC/SWIFT invalide.');
            }
        }

        if (empty($data['iban']))
        {
            $data['iban'] = '';
        }
        else
        {
            $data['iban'] = trim(strtoupper($data['iban']));
            $data['iban'] = preg_replace('![^\dA-Z]!', '', $data['iban']);

            if (!utils::checkIBAN($data['iban']))
            {
                throw new UserException('Code IBAN invalide.');
            }
        }

        return true;
    }
}

?>