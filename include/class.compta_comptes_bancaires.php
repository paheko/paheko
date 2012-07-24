<?php

require_once GARRADIN_ROOT . '/include/class.compta_comptes.php';

class Garradin_Compta_Comptes_Bancaires extends Garradin_Compta_Comptes
{
    const NUMERO_PARENT_COMPTES = 512;

    public function add($data)
    {
        $db = Garradin_DB::getInstance();

        $data['parent'] = self::NUMERO_PARENT_COMPTES;
        $data['id'] = false;

        $new_id = parent::add($data);

        $db = Garradin_DB::getInstance();
        $db->simpleInsert('compta_comptes_bancaires', array(
            'id'        =>  $new_id,
            'banque'    =>  $data['banque'],
            'rib'       =>  $data['rib'],
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
            'rib'       =>  $data['rib'],
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

    public function getList()
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleStatementFetch('SELECT * FROM compta_comptes AS c
            INNER JOIN compta_comptes_bancaires AS cc ON c.id = cc.id
            WHERE c.parent = '.self::NUMERO_PARENT_COMPTES.' ORDER BY c.id;');
    }

    protected function _checkFields(&$data)
    {
        parent::_checkFields($data);

        if (empty($data['banque']) || !trim($data['banque']))
        {
            throw new UserException('Le libellé ne peut rester vide.');
        }

        foreach (array('iban', 'bic', 'rib') as $champ)
        {
            if (!array_key_exists($champ, $data))
                $data[$champ] = '';
            else
            {
                $data[$champ] = trim($data[$champ]);
                $data[$champ] = preg_replace('![^\d]!', ' ', $data[$champ]);
                $data[$champ] = preg_replace('!\s{2,}!', ' ', $data[$champ]);
                $data[$champ] = trim($data[$champ]);
            }
        }

        return true;
    }
}

?>