<?php

require_once GARRADIN_ROOT . '/include/lib.miniskel.php';
require_once GARRADIN_ROOT . '/include/lib.squelette.filtres.php';

class Squelette extends miniSkel
{
    private $parent = null;
    private $_vars = array();

    private function _registerDefaultModifiers()
    {
        foreach (Squelette_Filtres::$filtres_php as $func=>$name)
        {
            if (is_string($func))
                $this->register_modifier($name, $func);
            else
                $this->register_modifier($name, $name);
        }

        foreach (get_class_methods('Squelette_Filtres') as $name)
        {
            $this->register_modifier($name, array('Squelette_Filtres', $name));
        }

        foreach (Squelette_Filtres::$filtres_alias as $name=>$func)
        {
            $this->register_modifier($name, array('Squelette_Filtres', $func));
        }
    }

    public function __construct()
    {
        $this->_registerDefaultModifiers();

        $config = Garradin_Config::getInstance();

        $this->assign('nom_asso', $config->get('nom_asso'));
        $this->assign('adresse_asso', $config->get('adresse_asso'));
        $this->assign('email_asso', $config->get('email_asso'));
        $this->assign('site_asso', $config->get('site_asso'));

        $this->assign('url_racine', WWW_URL);
        $this->assign('url_atom', WWW_URL . 'feed/atom/');
        $this->assign('url_css', WWW_URL . 'style/');
        $this->assign('url_admin', WWW_URL . 'admin/');

        $this->template_path = GARRADIN_ROOT . '/squelettes/';
    }

    private function _getTemplatePath($file)
    {
        return $this->template_path . '/' . $file;
    }

    protected function processInclude($args)
    {
        if (empty($args))
            throw new miniSkelMarkupException("Le tag INCLURE demande à préciser le fichier à inclure.");

        $file = key($args);

        if (empty($file) || !preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!', $file))
            throw new miniSkelMarkupException("INCLURE: le nom de fichier ne peut contenir que des caractères alphanumériques.");

        return '<?php $this->fetch("'.$file.'"); ?>';
    }

    protected function processVariable($name, $value, $applyDefault, $modifiers, $pre, $post, $context)
    {
        $out = '<?php ';
        $out.= 'if (isset($current[\''.$name.'\'])) $value = $current[\''.$name.'\'];';
        $out.= "\n";
        $out.= 'elseif (isset($this->parent[\''.$name.'\'])) $value = $this->parent[\''.$name.'\'];';
        $out.= "\n";
        $out.= 'elseif (isset($this->variables[\''.$name.'\'])) $value = $this->variables[\''.$name.'\'];';
        $out.= "\n";
        $out.= 'else $value = "";';
        $out.= "\n";

        if ($applyDefault)
        {
            $out.= 'if (is_string($value) && trim($value)) $value = htmlspecialchars($value, ENT_QUOTES, \'UTF-8\', false);';
            $out.= "\n";
        }

        // We process modifiers
        foreach ($modifiers as &$modifier)
        {
            if (!isset($this->modifiers[$modifier['name']]))
            {
                throw new miniSkelMarkupException('Filtre '.$modifier['name'].' inconnu !');
            }

            $args = 'array($value, ';
            foreach ($modifier['arguments'] as $arg)
            {
                if ($arg == 'debut_liste')
                    $args .= '$this->variables[\'debut_liste\'], ';
                else
                    $args .= '"'.str_replace('"', '\\"', $arg).'", ';
            }

            $args.= ')';

            $out.= '$value = call_user_func_array('.var_export($this->modifiers[$modifier['name']], true).', '.$args.');';
            $out.= "\n";
        }

        $out.= 'if ($value === true || trim($value) !== \'\'): ?>';

        // Getting pre-content
        if ($pre)
            $out .= $this->parseVariables($pre, false, $context);

        $out .= '<?php echo is_bool($value) ? "" : $value; ?>';

        // Getting post-content
        if ($post)
            $out .= $this->parseVariables($post, false, $context);

        $out .= '<?php endif; ?>';

        return $out;
    }

    protected function processLoop($loopName, $loopType, $loopCriterias, $loopContent, $preContent, $postContent, $altContent)
    {
        if ($loopType != 'articles' && $loopType != 'rubriques' && $loopType != 'pages')
        {
            throw new miniSkelMarkupException("Le type de boucle '".$loopType."' est inconnu.");
        }

        $out = '';
        $loopStart = '';
        $query = $where = $order = '';
        $limit = $begin = 0;

        $query = 'SELECT w.*, strftime(\\\'%s\\\', w.date_creation) AS date_creation, strftime(\\\'%s\\\', w.date_modification) AS date_modification';

        if (trim($loopContent))
        {
            $query .= ', r.contenu AS texte FROM wiki_pages AS w LEFT JOIN wiki_revisions AS r ON (w.id = r.id_page AND w.revision = r.revision) ';
        }

        $where = 'WHERE w.droit_lecture = -1 ';

        if ($loopType == 'articles')
        {
            $where .= 'AND (SELECT COUNT(id) FROM wiki_pages WHERE parent = w.id) = 0 ';
        }
        elseif ($loopType == 'rubriques')
        {
            $where .= 'AND (SELECT COUNT(id) FROM wiki_pages WHERE parent = w.id) > 0 ';
        }

        $allowed_fields = array('id', 'uri', 'titre', 'date', 'date_creation', 'date_modification',
            'parent', 'rubrique', 'revision', 'points', 'recherche');
        $search = $search_rank = false;

        foreach ($loopCriterias as $criteria)
        {
            if (isset($criteria['field']))
            {
                if (!in_array($criteria['field'], $allowed_fields))
                {
                    throw new miniSkelMarkupException("Critère '".$criteria['field']."' invalide pour la boucle '$loopName' de type '$loopType'.");
                }
                elseif ($criteria['field'] == 'rubrique')
                {
                    $criteria['field'] = 'parent';
                }
                elseif ($criteria['field'] == 'date')
                {
                    $criteria['field'] = 'date_creation';
                }
                elseif ($criteria['field'] == 'points')
                {
                    if ($criteria['action'] != miniSkel::ACTION_ORDER_BY)
                    {
                        throw new miniSkelMarkupException("Le critère 'points' n\'est pas valide dans ce contexte.");
                    }

                    $search_rank = true;
                }
            }

            switch ($criteria['action'])
            {
                case miniSkel::ACTION_ORDER_BY:
                    if (!$order)
                        $order = 'ORDER BY '.$criteria['field'].'';
                    else
                        $order .= ', '.$criteria['field'].'';
                    break;
                case miniSkel::ACTION_ORDER_DESC:
                    if ($order)
                        $order .= ' DESC';
                    break;
                case miniSkel::ACTION_LIMIT:
                    $begin = $criteria['begin'];
                    $limit = $criteria['number'];
                    break;
                case miniSkel::ACTION_MATCH_FIELD_BY_VALUE:
                    $where .= ' AND '.$criteria['field'].' '.$criteria['comparison'].' \\\'\'.$db->escapeString(\''.$criteria['value'].'\').\'\\\'';
                    break;
                case miniSkel::ACTION_MATCH_FIELD:
                {
                    if ($criteria['field'] == 'recherche')
                    {
                        $query = 'SELECT w.*, r.contenu AS texte, rank(matchinfo(wiki_recherche), 0, 1.0, 1.0) AS points FROM wiki_pages AS w INNER JOIN wiki_recherche AS r ON (w.id = r.id) ';
                        $where .= ' AND wiki_recherche MATCH \\\'\'.$db->escapeString($this->getVariable(\''.$criteria['field'].'\')).\'\\\'';
                        $search = true;
                    }
                    else
                    {
                        $where .= ' AND '.$criteria['field'].' = \\\'\'.$db->escapeString($this->getVariable(\''.$criteria['field'].'\')).\'\\\'';
                    }
                    break;
                }
                default:
                    break;
            }
        }

        if ($search_rank && !$search)
        {
            throw new miniSkelMarkupException("Le critère par points n'est possible que dans les boucles de recherche.");
        }

        if (trim($loopContent))
        {
            $loopStart .= '$row[\'url\'] = WWW_URL . $row[\'uri\']; ';
        }

        $query .= $where . ' ' . $order;

        if (!$limit || $limit > 100)
            $limit = 100;

        if ($limit)
        {
            $query .= ' LIMIT '.(is_numeric($begin) ? (int) $begin : '\'.$this->variables[\'debut_liste\'].\'').','.(int)$limit;
        }

        $hash = sha1(uniqid(mt_rand(), true));
        $out .= "<?php\n";
        $out .= '$this->parent =& $this->_vars[$parent_hash]; ';

        if ($search)
        {
            $out .= 'if (trim($this->getVariable(\'recherche\'))) { ';
        }

        $out .= '$result_'.$hash.' = $db->query(\''.$query.'\'); ';
        $out .= '$nb_rows = $db->countRows($result_'.$hash.'); ';

        if ($search)
        {
            $out .= '} else { $result_'.$hash.' = false; $nb_rows = 0; }';
        }

        $out .= "\n";
        $out .= '$this->_vars[\''.$hash.'\'] = array(\'_self_hash\' => \''.$hash.'\', \'_parent_hash\' => $parent_hash, \'total_boucle\' => $nb_rows, \'compteur_boucle\' => 0);';
        $out .= "\n";
        $out .= '$current =& $this->_vars[\''.$hash.'\']; $parent_hash = "'.$hash.'";';
        $out .= "\n";
        $out .= 'if ($nb_rows > 0): ?>';

        if ($preContent)
        {
            $out .= $this->parse($preContent, $loopName, self::PRE_CONTENT);
        }

        $out .= '<?php while ($row = $result_'.$hash.'->fetchArray(SQLITE3_ASSOC)):';
        $out .= "\n";
        $out .= '$this->_vars[\''.$hash.'\'][\'compteur_boucle\'] += 1; ';
        $out .= "\n";
        $out .= $loopStart;
        $out .= "\n";
        $out .= '$this->_vars[\''.$hash.'\'] = array_merge($this->_vars[\''.$hash.'\'], $row); ?>';

        $out .= $this->parseVariables($loopContent);

        $out .= '<?php endwhile; ?>';

        // we put the post-content after the loop content
        if ($postContent)
        {
            $out .= $this->parse($postContent, $loopName, self::POST_CONTENT);
        }

        if ($altContent)
        {
            $out .= '<?php else: ?>';
            $out .= $this->parse($altContent, $loopName, self::ALT_CONTENT);
        }

        $out .= '<?php endif; $parent_hash = $this->_vars[\''.$hash.'\'][\'_parent_hash\']; unset($result_'.$hash.', $nb_rows, $this->_vars[\''.$hash.'\']); $this->parent =& $this->_vars[$parent_hash]; ?>';

        return $out;
    }

    public function fetch($template, $no_display = false, $included = false)
    {
        $this->currentTemplate = $template;

        if (!self::compile_check($template, $this->template_path . $template))
        {
            if (!file_exists($this->template_path . $template))
            {
                throw new miniSkelMarkupException('Le squelette "'.$template.'" n\'existe pas.');
            }

            $content = file_get_contents($this->template_path . $template);
            $content = strtr($content, array('<?php' => '&lt;?php', '<?' => '<?php echo \'<?\'; ?>'));
            $content = $this->parse($content);
            $content = '<?php /* '.$template.' */ '.
                '$db = Garradin_DB::getInstance(); '.
                'if ($this->parent && !isset($parent_hash)) $parent_hash = $this->parent[\'_self_hash\']; '. // For included files
                'elseif (!$this->parent) $parent_hash = false; ?>' . $content;

            if (!$no_display)
            {
                self::compile_store($template, $content);
            }
        }

        if (!$no_display)
        {
            require self::compile_get_path($template);
        }
        else
        {
            eval($template);
        }

        return null;
    }

    public function dispatchURI()
    {
        $uri = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

        header('HTTP/1.1 200 OK', 200, true);

        if ($pos = strpos($uri, '?'))
        {
            $uri = substr($uri, 0, $pos);
        }

        if ($uri == '/')
        {
            $skel = 'sommaire.html';
        }
        elseif ($uri == '/feed/atom/')
        {
            header('Content-Type: application/atom+xml');
            $skel = 'atom.xml';
        }
        elseif (substr($uri, -1) == '/')
        {
            $skel = 'rubrique.html';
            $_GET['uri'] = $_REQUEST['uri'] = substr($uri, 1, -1);
        }
        else
        {
            $skel = 'article.html';
            $_GET['uri'] = $_REQUEST['uri'] = substr($uri, 1);
        }

        $this->display($skel);
    }

    static private function compile_get_path($path)
    {
        $hash = sha1($path);
        return GARRADIN_ROOT . '/cache/compiled/s_' . $hash . '.php';
    }

    static private function compile_check($tpl, $check)
    {
        if (!file_exists(self::compile_get_path($tpl)))
            return false;

        $time = filemtime(self::compile_get_path($tpl));

        if (empty($time))
        {
            return false;
        }

        if ($time < filemtime($check))
            return false;
        return $time;
    }

    static private function compile_store($tpl, $content)
    {
        $path = self::compile_get_path($tpl);

        if (!file_exists(dirname($path)))
        {
            mkdir(dirname($path));
        }

        file_put_contents($path, $content);
        return true;
    }

    static public function compile_clear($tpl)
    {
        $path = self::compile_get_path($tpl);

        if (file_exists($path))
            unlink($path);

        return true;
    }

    protected function getVariable($var)
    {
        if (isset($this->variables[$var]))
            return $this->variables[$var];
        elseif (isset($_REQUEST[$var]))
            return $_REQUEST[$var];
        else
            return null;
    }
}

?>