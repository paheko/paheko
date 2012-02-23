<?php

class Garradin_Wiki
{
    const LECTURE_PUBLIC = -1;
    const LECTURE_NORMAL = 0;
    const LECTURE_CATEGORIE = 1;

    const ECRITURE_NORMAL = 0;
    const ECRITURE_CATEGORIE = 1;

    const ITEMS_PER_PAGE = 25;

    protected $restriction_categorie = null;
    protected $restriction_droit = null;

    static public function transformTitleToURI($str)
    {
        $str = utils::transliterateToAscii($str);

        $str = preg_replace('!\s+!', '-', $str);
        $str = preg_replace('![^a-z0-9_-]!i', '', $str);

        return $str;
    }

    // Gestion des données ///////////////////////////////////////////////////////

    public function _checkFields(&$data)
    {
        if (isset($data['titre']) && !trim($data['titre']))
        {
            throw new UserException('Le titre ne peut rester vide.');
        }

        if (isset($data['uri']) && !trim($data['uri']))
        {
            throw new UserException('L\'adresse de la page ne peut rester vide.');
        }

        if (isset($data['droit_lecture']))
        {
            $data['droit_lecture'] = (int) $data['droit_lecture'];

            if ($data['droit_lecture'] < -1)
            {
                $data['droit_lecture'] = 0;
            }
        }

        if (isset($data['droit_ecriture']))
        {
            $data['droit_ecriture'] = (int) $data['droit_ecriture'];

            if ($data['droit_ecriture'] < 0)
            {
                $data['droit_ecriture'] = 0;
            }
        }

        if (isset($data['parent']))
        {
            $data['parent'] = (int) $data['parent'];

            if ($data['parent'] < 0)
            {
                $data['parent'] = 0;
            }
        }

        return true;
    }

    public function create($data = array())
    {
        $this->_checkFields($data);
        $db = Garradin_DB::getInstance();

        if (!empty($data['uri']))
        {
            $data['uri'] = self::transformTitleToURI($data['uri']);

            if ($db->simpleQuerySingle('SELECT 1 FROM wiki_pages WHERE uri = ? LIMIT 1;', false, $data['uri']))
            {
                throw new UserException('Cette adresse de page est déjà utilisée pour une autre page, il faut en choisir une autre.');
            }
        }
        else
        {
            $data['uri'] = self::transformTitleToURI($data['titre']);

            if (!trim($data['uri']) || $db->simpleQuerySingle('SELECT 1 FROM wiki_pages WHERE uri = ? LIMIT 1;', false, $data['uri']))
            {
                $data['uri'] .= '_' . date('d-m-Y_H-i-s');
            }
        }

        $db->simpleInsert('wiki_pages', $data);
        return $db->lastInsertRowId();
    }

    public function edit($id, $data = array())
    {
        $db = Garradin_DB::getInstance();
        $this->_checkFields($data);

        if (isset($data['uri']))
        {
            $data['uri'] = self::transformTitleToURI($data['uri']);

            if ($db->simpleQuerySingle('SELECT 1 FROM wiki_pages WHERE uri = ? AND id != ? LIMIT 1;', false, $data['uri'], (int)$id))
            {
                throw new UserException('Cette adresse de page est déjà utilisée pour une autre page, il faut en choisir une autre.');
            }
        }

        $data['date_modification'] = new SQLite3_Instruction('CURRENT_TIMESTAMP');

        $db->simpleUpdate('wiki_pages', $data, 'id = '.(int)$id);
        return true;
    }

    public function get($id)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleQuerySingle('SELECT *,
            strftime(\'%s\', date_creation) AS date_creation,
            strftime(\'%s\', date_modification) AS date_modification
            FROM wiki_pages WHERE id = ? LIMIT 1;', true, (int)$id);
    }

    public function editRevision($id, $revision_edition = 0, $data)
    {
        $db = Garradin_DB::getInstance();

        $revision = $db->simpleQuerySingle('SELECT revision FROM wiki_pages WHERE id = ?;', false, (int)$id);

        // ?! L'ID fournit ne correspond à rien ?
        if ($revision === false)
        {
            throw new RuntimeException('La page demandée n\'existe pas.');
        }

        // Pas de révision
        if ($revision == 0 && !trim($data['contenu']))
        {
            return true;
        }

        // Il faut obligatoirement fournir un ID d'auteur
        if (empty($data['id_auteur']))
        {
            throw new BadMethodCallException('Aucun ID auteur de fourni.');
        }

        $contenu = $db->simpleQuerySingle('SELECT contenu FROM wiki_revisions WHERE revision = ? AND id_page = ?;', false, (int)$revision, (int)$id);

        // Pas de changement au contenu, pas la peine d'enregistrer une nouvelle révision
        if (trim($contenu) == trim($data['contenu']))
        {
            return true;
        }

        // Révision sur laquelle est basée la nouvelle révision
        // utilisé pour vérifier que le contenu n'a pas été modifié depuis qu'on
        // a chargé la page d'édition
        if ($revision > $revision_edition)
        {
            throw new UserException('La page a été modifiée depuis le début de votre modification.');
        }

        if (empty($data['chiffrement']))
            $data['chiffrement'] = 0;

        if (!isset($data['modification']) || !trim($data['modification']))
            $data['modification'] = null;

        // Incrémentons le numéro de révision
        $revision++;

        $data['id_page'] = $id;
        $data['revision'] = $revision;

        $db->simpleInsert('wiki_revisions', $data);
        $db->simpleUpdate('wiki_pages', array(
            'revision'          =>  $revision,
            'date_modification' =>  new SQLite3_Instruction('CURRENT_TIMESTAMP'),
        ), 'id = '.(int)$id);

        return true;
    }

    public function search($query)
    {
        $db = Garradin_DB::getInstance();
        // FIXME
    }

    public function setRestrictionCategorie($id, $droit_wiki)
    {
        $this->restriction_categorie = $id;
        $this->restriction_droit = $droit_wiki;
        return true;
    }

    protected function _getLectureClause()
    {
        if (is_null($this->restriction_categorie))
        {
            throw new UnexpectedValueException('setRestrictionCategorie doit être appelé auparavant.');
        }

        if ($this->restriction_droit == Garradin_Membres::DROIT_AUCUN)
        {
            throw new UserException('Vous n\'avez pas accès au wiki.');
        }

        if ($this->restriction_droit == Garradin_Membres::DROIT_ADMIN)
            return '1';

        return '(droit_lecture = '.self::LECTURE_NORMAL.' OR droit_lecture = '.self::LECTURE_PUBLIC.'
            OR droit_lecture = '.(int)$this->restriction_categorie.')';
    }

    public function canReadPage($lecture)
    {
        if (is_null($this->restriction_categorie))
        {
            throw new UnexpectedValueException('setRestrictionCategorie doit être appelé auparavant.');
        }

        if ($this->restriction_droit < Garradin_Membres::DROIT_ACCES)
        {
            return false;
        }

        if ($this->restriction_droit == Garradin_Membres::DROIT_ADMIN
            || $lecture == self::LECTURE_NORMAL || $lecture == self::LECTURE_PUBLIC
            || $lecture == $this->restriction_categorie)
            return true;

        return false;
    }

    public function canWritePage($ecriture)
    {
        if (is_null($this->restriction_categorie))
        {
            throw new UnexpectedValueException('setRestrictionCategorie doit être appelé auparavant.');
        }

        if ($this->restriction_droit < Garradin_Membres::DROIT_ECRITURE)
        {
            return false;
        }

        if ($this->restriction_droit == Garradin_Membres::DROIT_ADMIN
            || $ecriture == self::ECRITURE_NORMAL
            || $ecriture == $this->restriction_categorie)
            return true;

        return false;
    }

    public function getList($parent = 0)
    {
        $db = Garradin_DB::getInstance();

        return $db->simpleStatementFetch(
            'SELECT id, revision, uri, titre,
                strftime(\'%s\', date_creation) AS date_creation,
                strftime(\'%s\', date_modification) AS date_modification
                FROM wiki_pages
                WHERE parent = ? AND '.$this->_getLectureClause().'
                ORDER BY titre LIMIT 500;',
            SQLITE3_ASSOC,
            (int) $parent
        );
    }

    public function getById($id)
    {
        $db = Garradin_DB::getInstance();
        $page = $db->simpleQuerySingle('SELECT *,
                strftime(\'%s\', date_creation) AS date_creation,
                strftime(\'%s\', date_modification) AS date_modification
                FROM wiki_pages
                WHERE id = ? AND '.$this->_getLectureClause().';', true, (int)$id);

        if (!$page)
        {
            return false;
        }

        if ($page['revision'] > 0)
        {
            $page['contenu'] = $db->simpleQuerySingle('SELECT * FROM wiki_revisions
                WHERE id_page = ? AND revision = ?;', true, (int)$page['id'], (int)$page['revision']);
        }
        else
        {
            $page['contenu'] = false;
        }

        return $page;
    }

    public function getByURI($uri)
    {
        $db = Garradin_DB::getInstance();
        $page = $db->simpleQuerySingle('SELECT *,
                strftime(\'%s\', date_creation) AS date_creation,
                strftime(\'%s\', date_modification) AS date_modification
                FROM wiki_pages
                WHERE uri = ? AND '.$this->_getLectureClause().';', true, trim($uri));

        if (!$page)
        {
            return false;
        }

        if ($page['revision'] > 0)
        {
            $page['contenu'] = $db->simpleQuerySingle('SELECT * FROM wiki_revisions
                WHERE id_page = ? AND revision = ?;', true, (int)$page['id'], (int)$page['revision']);
        }
        else
        {
            $page['contenu'] = false;
        }

        return $page;
    }

    public function listRecentModifications($page = 1)
    {
        $begin = ($page - 1) * self::ITEMS_PER_PAGE;

        $db = Garradin_DB::getInstance();

        return $db->simpleStatementFetch('SELECT *,
                strftime(\'%s\', date_creation) AS date_creation,
                strftime(\'%s\', date_modification) AS date_modification
                FROM wiki_pages
                WHERE '.$this->_getLectureClause().'
                ORDER BY date_modification DESC;', SQLITE3_ASSOC);
    }

    public function countRecentModifications()
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleQuerySingle('SELECT COUNT(*) FROM wiki_pages WHERE '.$this->_getLectureClause().';');
    }
}

?>