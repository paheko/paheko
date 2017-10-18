<?php

namespace Garradin;

class Wiki
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
        $str = Utils::transliterateToAscii($str);

        $str = preg_replace('![^\w\d_-]!i', '-', $str);
        $str = preg_replace('!-{2,}!', '-', $str);
        $str = trim($str, '-');

        return $str;
    }

    // Gestion des données ///////////////////////////////////////////////////////

    public function _checkFields(&$data)
    {
        $db = DB::getInstance();

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

            if (!$db->firstColumn('SELECT 1 FROM wiki_pages WHERE id = ?;', $data['parent']))
            {
                $data['parent'] = 0;
            }
        }

        return true;
    }

    public function create($data = [])
    {
        $this->_checkFields($data);
        $db = DB::getInstance();

        if (!empty($data['uri']))
        {
            $data['uri'] = self::transformTitleToURI($data['uri']);

            if ($db->firstColumn('SELECT 1 FROM wiki_pages WHERE uri = ? LIMIT 1;', $data['uri']))
            {
                throw new UserException('Cette adresse de page est déjà utilisée pour une autre page, il faut en choisir une autre.');
            }
        }
        else
        {
            $data['uri'] = self::transformTitleToURI($data['titre']);

            if (!trim($data['uri']) || $db->firstColumn('SELECT 1 FROM wiki_pages WHERE uri = ? LIMIT 1;', $data['uri']))
            {
                $data['uri'] .= '_' . date('d-m-Y_H-i-s');
            }
        }

        $db->insert('wiki_pages', $data);
        $id = $db->lastInsertRowId();

        // On ne peut utiliser un trigger pour insérer dans la recherche
        // car les tables virtuelles font des opérations qui modifient
        // last_insert_rowid() et donc résultat incohérent
        $db->insert('wiki_recherche', ['id' => $id, 'titre' => $data['titre']]);

        return $id;
    }

    public function edit($id, $data = [])
    {
        $db = DB::getInstance();
        $this->_checkFields($data);

        // Modification de la date de création: vérification que le format est bien conforme SQLite
        if (isset($data['date_creation']))
        {
        	if (!Utils::checkDateTime($data['date_creation']))
	        {
	            throw new UserException('Date invalide: '.($data['date_creation'] ?: 'date non reconnue'));
	        }

	        // On stocke la date en UTC, pas dans le fuseau local
	        $data['date_creation'] = gmdate('Y-m-d H:i:s', strtotime($data['date_creation']));
	    }
        
        if (isset($data['uri']))
        {
            $data['uri'] = self::transformTitleToURI($data['uri']);

            if ($db->firstColumn('SELECT 1 FROM wiki_pages WHERE uri = ? AND id != ? LIMIT 1;', $data['uri'], (int)$id))
            {
                throw new UserException('Cette adresse de page est déjà utilisée pour une autre page, il faut en choisir une autre.');
            }
        }

        if (isset($data['droit_lecture']) && $data['droit_lecture'] >= self::LECTURE_CATEGORIE)
        {
            $data['droit_ecriture'] = $data['droit_lecture'];
        }

        if (isset($data['parent']) && (int)$data['parent'] == (int)$id)
        {
            $data['parent'] = 0;
        }

        $data['date_modification'] = gmdate('Y-m-d H:i:s');

        $db->update('wiki_pages', $data, 'id = :id', ['id' => (int)$id]);
        return true;
    }

    public function delete($id)
    {
        $db = DB::getInstance();

        // Ne pas permettre de supprimer une page qui a des sous-pages
        if ($db->firstColumn('SELECT 1 FROM wiki_pages WHERE parent = ? LIMIT 1;', (int)$id))
        {
            return false;
        }

        // Suppression des fichiers liés
        $files = Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $id, null);

        foreach ($files as $file)
        {
            $file = new Fichiers($file->id, $file);
            $file->remove();
        }

        $db->delete('wiki_revisions', 'id_page = ?', (int)$id);
        $db->delete('wiki_recherche', 'id = ?', (int)$id);
        $db->delete('wiki_pages', 'id = ?', (int)$id);

        return true;
    }

    public function get($id)
    {
        $db = DB::getInstance();
        return $db->first('SELECT *,
            strftime(\'%s\', date_creation) AS date_creation,
            strftime(\'%s\', date_modification) AS date_modification
            FROM wiki_pages WHERE id = ? LIMIT 1;', (int)$id);
    }

    public function getTitle($id)
    {
        $db = DB::getInstance();
        return $db->firstColumn('SELECT titre FROM wiki_pages WHERE id = ? LIMIT 1;', (int)$id);
    }

    public function getRevision($id, $rev)
    {
        $db = DB::getInstance();
        $champ_id = Config::getInstance()->get('champ_identite');

        return $db->first('SELECT r.revision, r.modification, r.id_auteur, r.contenu,
            strftime(\'%s\', r.date) AS date, LENGTH(r.contenu) AS taille, m.'.$champ_id.' AS nom_auteur,
            r.chiffrement
            FROM wiki_revisions AS r LEFT JOIN membres AS m ON m.id = r.id_auteur
            WHERE r.id_page = ? AND revision = ? LIMIT 1;', (int) $id, (int) $rev);
    }

    public function listRevisions($id)
    {
        $db = DB::getInstance();
        $champ_id = Config::getInstance()->get('champ_identite');

        // FIXME pagination au lieu de bloquer à 1000
        return $db->get('SELECT r.revision, r.modification, r.id_auteur,
            strftime(\'%s\', r.date) AS date, LENGTH(r.contenu) AS taille, m.'.$champ_id.' AS nom_auteur,
            LENGTH(r.contenu) - (SELECT LENGTH(contenu) FROM wiki_revisions WHERE id_page = r.id_page AND revision < r.revision ORDER BY revision DESC LIMIT 1)
            AS diff_taille, r.chiffrement
            FROM wiki_revisions AS r LEFT JOIN membres AS m ON m.id = r.id_auteur
            WHERE r.id_page = ? ORDER BY r.revision DESC LIMIT 1000;', (int) $id);
    }

    public function editRevision($id, $revision_edition = 0, $data)
    {
        $db = DB::getInstance();

        $revision = $db->firstColumn('SELECT revision FROM wiki_pages WHERE id = ?;', (int)$id);

        // ?! L'ID fournit ne correspond à rien ?
        if ($revision === false)
        {
            throw new \RuntimeException('La page demandée n\'existe pas.');
        }

        // Pas de révision
        if ($revision == 0 && !trim($data['contenu']))
        {
            return true;
        }

        // Il faut obligatoirement fournir un ID d'auteur
        if (empty($data['id_auteur']) && $data['id_auteur'] !== null)
        {
            throw new \BadMethodCallException('Aucun ID auteur de fourni.');
        }

        $contenu = $db->firstColumn('SELECT contenu FROM wiki_revisions WHERE revision = ? AND id_page = ?;', (int)$revision, (int)$id);

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

        $db->insert('wiki_revisions', $data);
        $db->update('wiki_pages', [
            'revision'          =>  $revision,
            'date_modification' =>  gmdate('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => (int)$id]);

        return true;
    }

    public function search($search)
    {
        $query = sprintf('SELECT
            p.uri, r.*, snippet(wiki_recherche, \'<b>\', \'</b>\', \'...\', -1, -50) AS snippet,
            rank(matchinfo(wiki_recherche), 0, 1.0, 1.0) AS points
            FROM wiki_recherche AS r INNER JOIN wiki_pages AS p ON p.id = r.id
            WHERE %s AND wiki_recherche MATCH ?
            ORDER BY points DESC LIMIT 0,50;', $this->_getLectureClause('p.'));

        return DB::getInstance()->get($query, $search);
    }

    public function setRestrictionCategorie($id, $droit_wiki)
    {
        $this->restriction_categorie = $id;
        $this->restriction_droit = $droit_wiki;
        return true;
    }

    protected function _getLectureClause($prefix = '')
    {
        if (is_null($this->restriction_categorie))
        {
            throw new \UnexpectedValueException('setRestrictionCategorie doit être appelé auparavant.');
        }

        if ($this->restriction_droit == Membres::DROIT_AUCUN)
        {
            throw new UserException('Vous n\'avez pas accès au wiki.');
        }

        if ($this->restriction_droit == Membres::DROIT_ADMIN)
            return '1';

        return '('.$prefix.'droit_lecture = '.self::LECTURE_NORMAL.' OR '.$prefix.'droit_lecture = '.self::LECTURE_PUBLIC.'
            OR '.$prefix.'droit_lecture = '.(int)$this->restriction_categorie.')';
    }

    public function canReadPage($lecture)
    {
        if (is_null($this->restriction_categorie))
        {
            throw new \UnexpectedValueException('setRestrictionCategorie doit être appelé auparavant.');
        }

        if ($this->restriction_droit < Membres::DROIT_ACCES)
        {
            return false;
        }

        if ($this->restriction_droit == Membres::DROIT_ADMIN
            || $lecture == self::LECTURE_NORMAL || $lecture == self::LECTURE_PUBLIC
            || $lecture == $this->restriction_categorie)
            return true;

        return false;
    }

    public function canWritePage($ecriture)
    {
        if (is_null($this->restriction_categorie))
        {
            throw new \UnexpectedValueException('setRestrictionCategorie doit être appelé auparavant.');
        }

        if ($this->restriction_droit < Membres::DROIT_ECRITURE)
        {
            return false;
        }

        if ($this->restriction_droit == Membres::DROIT_ADMIN
            || $ecriture == self::ECRITURE_NORMAL
            || $ecriture == $this->restriction_categorie)
            return true;

        return false;
    }

    public function getList($parent = 0, $order_by_date = false)
    {
        $order = ($order_by_date ? 'date_creation DESC' : 'transliterate_to_ascii(titre) COLLATE NOCASE');

        $query = sprintf('SELECT id, revision, uri, titre,
                strftime(\'%%s\', date_creation) AS date_creation,
                strftime(\'%%s\', date_modification) AS date_modification
                FROM wiki_pages
                WHERE parent = ? AND %s ORDER BY %s LIMIT 500;', $this->_getLectureClause(), $order);

        return DB::getInstance()->get($query, (int) $parent);
    }

    public function hasChildren($parent, $public_only = false)
    {
        $db = DB::getInstance();
        $public = !$public_only ? '' : ' AND ' . $db->where('droit_lecture', self::LECTURE_PUBLIC);
        return $db->test('wiki_pages', $db->where('parent', (int)$parent) . $public);
    }

    public function getById($id)
    {
        $db = DB::getInstance();
        $page = $db->first('SELECT *,
            strftime(\'%s\', date_creation) AS date_creation,
            strftime(\'%s\', date_modification) AS date_modification
            FROM wiki_pages
            WHERE id = ?;', (int)$id);

        if (!$page)
        {
            return false;
        }

        $page->contenu = false;

        if ($page->revision > 0)
        {
            $page->contenu = $db->first('SELECT * FROM wiki_revisions
                WHERE id_page = ? AND revision = ?;', (int)$page->id, (int)$page->revision);
        }

        return $page;
    }

    public function getByURI($uri)
    {
        $id = DB::getInstance()->firstColumn('SELECT id FROM wiki_pages WHERE uri = ?;', $uri);

        if (!$id)
        {
            return false;
        }

        return $this->getByID($id);
    }

    public function listRecentModifications($page = 1)
    {
        $begin = ($page - 1) * self::ITEMS_PER_PAGE;

        $db = DB::getInstance();

        return $db->get('SELECT *,
                strftime(\'%s\', date_creation) AS date_creation,
                strftime(\'%s\', date_modification) AS date_modification
                FROM wiki_pages
                WHERE '.$this->_getLectureClause().'
                ORDER BY date_modification DESC;');
    }

    public function countRecentModifications()
    {
        $db = DB::getInstance();
        return $db->firstColumn('SELECT COUNT(*) FROM wiki_pages WHERE '.$this->_getLectureClause().';');
    }

    public function listBackBreadCrumbs($id)
    {
        if ($id == 0)
            return [];

        $db = DB::getInstance();
        $flat = [];
        $max = 0;

        while ($id > 0 && $max++ < 10)
        {
            $res = $db->first('SELECT parent, titre, uri
                FROM wiki_pages WHERE id = ? LIMIT 1;', (int)$id);

            $flat[] = [
                'id'        =>  $id,
                'titre'     =>  $res->titre,
                'uri'       =>  $res->uri,
            ];

            if ($id == $res->parent)
            {
                throw new Exception('Parent! ' . $id . '/' . $res->parent);
            }

            $id = (int)$res->parent;
        }

        return array_reverse($flat);
    }

    public function listBackParentTree($id)
    {
        $db = DB::getInstance();
        $flat = [
            (object) [
                'id' => 0,
                'parent' => null,
                'titre' => 'Racine',
                'children' => $db->getGrouped('SELECT id, parent, titre FROM wiki_pages
                    WHERE parent = ? ORDER BY transliterate_to_ascii(titre) COLLATE NOCASE;',
                    0)
            ]
        ];

        $max = 0;

        do
        {
            $parent = $db->get('SELECT parent FROM wiki_pages WHERE id = ? LIMIT 1;', (int)$id);

            $flat[$id] = (object) [
                'id'        =>  $id,
                'parent'    =>  $id ? (int)$parent : null,
                'titre'     =>  $id ? $this->getTitle($id) : 'Racine',
                'children'  =>  $db->getGrouped('SELECT id, parent, titre FROM wiki_pages
                    WHERE parent = ? ORDER BY transliterate_to_ascii(titre) COLLATE NOCASE;',
                    (int)$id)
            ];

            $id = (int)$parent;
        }
        while ($id != 0 && $max++ < 20);

        $tree = [];
        foreach ($flat as $id=>&$node)
        {
            if (is_null($node->parent))
            {
                $tree[$id] = &$node;
            }
            else
            {
                if (!isset($flat[$node->parent]->children))
                {
                    $flat[$node->parent]->children = [];
                }

                $flat[$node->parent]->children[$id] = &$node;
            }
        }

        return $tree;
    }
}
