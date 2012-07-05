<?php

require_once GARRADIN_ROOT . '/include/class.compta_comptes.php';

class Garradin_Compta_Comptes_Bancaires extends Garradin_Compta_Comptes
{
    public function add($data)
    {
        $data['parent'] = 512;

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
        ), 'id = \''.trim($id).'\'');

        return true;
    }

    public function get($id)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleQuerySingle('SELECT * FROM compta_comptes AS c
            INNER JOIN compta_comptes_bancaires AS cc
            ON c.id = cc.id
            WHERE c.id = ?;', true, $id);
    }
}

?>