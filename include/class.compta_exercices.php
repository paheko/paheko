<?php

class Garradin_Compta_Exercices
{
    public function add($data)
    {
        $this->_checkFields($data);

        $db = Garradin_DB::getInstance();

        if ($db->simpleQuerySingle('SELECT 1 FROM compta_exercices WHERE
            (debut <= :debut AND fin >= :debut) OR (debut <= :fin AND fin >= :fin);', false,
            array('debut' => $data['debut'], 'fin' => $data['fin'])))
        {
            throw new UserException('La date de début ou de fin se recoupe avec un autre exercice.');
        }

        if ($db->querySingle('SELECT 1 FROM compta_exercices WHERE clos = 0;'))
        {
            throw new UserException('Il n\'est pas possible de créer un nouvel exercice tant qu\'il existe un exercice non-clos.');
        }

        $db->simpleInsert('compta_exercices', array(
            'libelle'   =>  trim($data['libelle']),
            'debut'     =>  $data['debut'],
            'fin'       =>  $data['fin'],
        ));

        return $db->lastInsertRowId();
    }

    public function edit($id, $data)
    {
        $db = Garradin_DB::getInstance();

        $this->_checkFields($data);

        if ($db->simpleQuerySingle('SELECT 1 FROM compta_exercices WHERE id != :id AND
            ((debut <= :debut AND fin >= :debut) OR (debut <= :fin AND fin >= :fin));', false,
            array('debut' => $data['debut'], 'fin' => $data['fin'], 'id' => (int) $id)))
        {
            throw new UserException('La date de début ou de fin se recoupe avec un autre exercice.');
        }

        $db->simpleUpdate('compta_comptes', array(
            'libelle'   =>  trim($data['libelle']),
            'debut'     =>  $data['debut'],
            'fin'       =>  $data['fin'],
        ), 'id = \''.(int)$id.'\'');

        return true;
    }

    public function delete($id)
    {
        $db = Garradin_DB::getInstance();

        // Ne pas supprimer un compte qui est utilisé !
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_journal WHERE id_exercice = ? LIMIT 1;', false, $id))
        {
            throw new UserException('Cet exercice ne peut être supprimé car des opérations comptables y sont liées.');
        }

        $db->simpleExec('DELETE FROM compta_exercices WHERE id = ?;', (int)$id);

        return true;
    }

    public function get($id)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleQuerySingle('SELECT *, strftime(\'%s\', debut) AS debut,
            strftime(\'%s\', fin) AS fin FROM compta_exercices WHERE id = ?;', true, (int)$id);
    }

    public function getList()
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleStatementFetchAssocKey('SELECT id, *, strftime(\'%s\', debut) AS debut,
            strftime(\'%s\', fin) AS fin FROM compta_exercices ORDER BY debut;', SQLITE3_ASSOC);
    }

    protected function _checkFields(&$data)
    {
        $db = Garradin_DB::getInstance();

        if (empty($data['libelle']) || !trim($data['libelle']))
        {
            throw new UserException('Le libellé ne peut rester vide.');
        }

        $data['libelle'] = trim($data['libelle']);

        if (empty($data['debut']) || !checkdate(substr($data['debut'], 5, 2), substr($data['debut'], 8, 2), substr($data['debut'], 0, 4)))
        {
            throw new UserException('Date de début vide ou invalide.');
        }

        if (empty($data['fin']) || !checkdate(substr($data['fin'], 5, 2), substr($data['fin'], 8, 2), substr($data['fin'], 0, 4)))
        {
            throw new UserException('Date de fin vide ou invalide.');
        }

        return true;
    }
}

?>