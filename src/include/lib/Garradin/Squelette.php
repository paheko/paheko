<?php

namespace Garradin;

class Squelette_Snippet
{
    const TEXT = 0;
    const PHP = 1;
    const GUESS = 2;
    const OBJ = 3;

    protected $_content = [];

    protected function _getType($type, $value)
    {
        if ($type == self::GUESS)
        {
            if ($value instanceof Squelette_Snippet)
                return self::OBJ;
            else
                return self::TEXT;
        }

        return $type;
    }

    public function __construct($type = self::TEXT, $value = '')
    {
        $type = $this->_getType($type, $value);

        if ($type == self::OBJ)
        {
            $this->_content = $value->get();
        }
        else
        {
            $this->_content[] = (string) (int) $type . $value;
        }

        unset($value);
    }

    public function prepend($type = self::TEXT, $value, $pos = false)
    {
        $type = $this->_getType($type, $value);

        if ($type == self::OBJ)
        {
            if ($pos)
            {
                array_splice($this->_content, $pos, 0, $value->get());
            }
            else
            {
                $this->_content = array_merge($value->get(), $this->_content);
            }
        }
        else
        {
            $value = (string) (int) $type . $value;

            if ($pos)
            {
                array_splice($this->_content, $pos, 0, $value);
            }
            else
            {
                array_unshift($this->_content, $value);
            }
        }

        unset($value);
    }

    public function append($type = self::TEXT, $value, $pos = false)
    {
        $type = $this->_getType($type, $value);

        if ($type == self::OBJ)
        {
            if ($pos)
            {
                array_splice($this->_content, $pos + 1, 0, $value->get());
            }
            else
            {
                $this->_content = array_merge($this->_content, $value->get());
            }
        }
        else
        {
            $value = (string) (int) $type . $value;

            if ($pos)
            {
                array_splice($this->_content, $pos + 1, 0, $value);
            }
            else
            {
                array_push($this->_content, $value);
            }
        }

        unset($value);
    }

    public function output($in_php = false)
    {
        $out = '';
        $php = $in_php ?: false;

        foreach ($this->_content as $line)
        {
            if ($line[0] == self::PHP && !$php)
            {
                $php = true;
                $out .= '<?php ';
            }
            elseif ($line[0] == self::TEXT && $php)
            {
                $php = false;
                $out .= ' ?>';
            }

            $out .= substr($line, 1);

            if ($line[0] == self::PHP)
            {
                $out .= "\n";
            }
        }

        if ($php && !$in_php)
        {
            $out .= ' ?>';
        }

        $this->_content = [];

        return $out;
    }

    public function __toString()
    {
        return $this->output(false);
    }

    public function get()
    {
        return $this->_content;
    }

    public function replace($key, $type = self::TEXT, $value)
    {
        $type = $this->_getType($type, $value);

        if ($type == self::OBJ)
        {
            array_splice($this->_content, $key, 1, $value->get());
        }
        else
        {
            $this->_content[$key] = (string) (int) $type . $value;
        }

        unset($value);
    }
}

class Squelette extends \KD2\MiniSkel
{
    private $parent = null;
    private $current = null;
    private $_vars = [];

    private function _registerDefaultModifiers()
    {
        foreach (Squelette_Filtres::$filtres_php as $func=>$name)
        {
            if (is_string($func))
                $this->register_modifier($name, $func);
            else
                $this->register_modifier($name, $name);
        }

        foreach (get_class_methods('Garradin\Squelette_Filtres') as $name)
        {
            $this->register_modifier($name, ['Garradin\Squelette_Filtres', $name]);
        }

        foreach (Squelette_Filtres::$filtres_alias as $name=>$func)
        {
            $this->register_modifier($name, ['Garradin\Squelette_Filtres', $func]);
        }

        if (file_exists(DATA_ROOT . '/www/squelettes/mes_filtres.php'))
        {
            require_once DATA_ROOT . '/www/squelettes/mes_filtres.php';
        }
    }

    private function _registerDefaultTags()
    {
        $config = Config::getInstance();

        $this->assign('nom_asso', $config->get('nom_asso'));
        $this->assign('adresse_asso', $config->get('adresse_asso'));
        $this->assign('email_asso', $config->get('email_asso'));
        $this->assign('site_asso', $config->get('site_asso'));

        $this->assign('url_racine', WWW_URL);
        $this->assign('url_site', WWW_URL);
        $this->assign('url_atom', WWW_URL . 'feed/atom/');
        $this->assign('url_elements', WWW_URL . 'squelettes/');
        $this->assign('url_admin', WWW_URL . 'admin/');

        $url = file_exists(DATA_ROOT . '/www/squelettes/default.css')
            ? WWW_URL . 'squelettes/default.css'
            : WWW_URL . 'squelettes-dist/default.css';

        $this->assign('url_css_defaut', $url);

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            if (function_exists('locale_accept_from_http'))
            {
	           $lang = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            }
            else
            {
                $lang = preg_replace('/[^a-z]/i', '', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
                $lang = strtolower(substr($lang, 0, 2));
            }

	        $lang = strtolower(substr($lang, 0, 2));
	    }
	    else
	    {
	    	$lang = '';
	    }

        $this->assign('langue_visiteur', $lang);
    }

    public function __construct()
    {
        $this->_registerDefaultModifiers();
        $this->_registerDefaultTags();
    }

    protected function processInclude($args)
    {
        if (empty($args))
            throw new \KD2\MiniSkelMarkupException("Le tag INCLURE demande à préciser le fichier à inclure.");

        $file = key($args);

        if (empty($file) || !preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!', $file))
            throw new \KD2\MiniSkelMarkupException("INCLURE: le nom de fichier ne peut contenir que des caractères alphanumériques.");

        return new Squelette_Snippet(1, '$this->fetch("'.$file.'", false);');
    }

    protected function processVariable($name, $applyDefault, $modifiers, $pre, $post, $context)
    {
        if ($context == self::CONTEXT_IN_ARG)
        {
            $out = new Squelette_Snippet(1, '$this->getVariable(\''.$name.'\')');

            if ($pre)
            {
                $out->prepend(2, $pre);
            }

            if ($post)
            {
                $out->append(2, $post);
            }

            return $out;
        }

        $out = new Squelette_Snippet(1, '$value = $this->getVariable(\''.$name.'\');');

        // We process modifiers
        foreach ($modifiers as &$modifier)
        {
            if (!isset($this->modifiers[$modifier['name']]))
            {
                throw new \KD2\MiniSkelMarkupException('Filtre '.$modifier['name'].' inconnu !');
            }

            $out->append(1, '$value = call_user_func_array('.var_export($this->modifiers[$modifier['name']], true).', [$value, ');

            foreach ($modifier['arguments'] as $arg)
            {
                if ($arg == 'debut_liste')
                {
                    $out->append(1, '$this->getVariable(\'debut_liste\')');
                }
                elseif ($arg instanceOf Squelette_Snippet)
                {
                    $out->append(3, $arg);
                }
                else
                {
                    //if (preg_match('!getVariable!', $arg)) throw new Exception("lol");
                    $out->append(1, '"'.str_replace('"', '\\"', $arg).'"');
                }

                $out->append(1, ', ');
            }

            $out->append(1, ']);');

            if (in_array($modifier['name'], Squelette_Filtres::$desactiver_defaut))
            {
                $applyDefault = false;
            }
        }

        if ($applyDefault)
        {
            $out->append(1, 'if (is_string($value) && trim($value)) $value = htmlspecialchars($value, ENT_QUOTES, \'UTF-8\', false);');
        }

        $out->append(1, 'if ($value === true || trim($value) !== \'\'):');

        // Getting pre-content
        if ($pre)
        {
            $out->append(2, $pre);
        }

        $out->append(1, 'echo is_bool($value) ? "" : $value;');

        // Getting post-content
        if ($post)
        {
            $out->append(2, $post);
        }

        $out->append(1, 'endif;');

        return $out;
    }

    protected function processLoop($loopName, $loopType, $loopCriterias, $loopContent, $preContent, $postContent, $altContent)
    {
        $query = $loop_start = '';
        $query_args = [];
        $db = DB::getInstance();

        // Types de boucles natifs
        if ($loopType == 'articles' || $loopType == 'rubriques' || $loopType == 'pages')
        {
            $where = $order = '';
            $limit = $begin = 0;

            $query = 'SELECT w.*, ';
            $query.= 'strftime(\'%s\', w.date_creation) AS date_creation, ';
            $query.= 'strftime(\'%s\', w.date_modification) AS date_modification';

            if (trim($loopContent))
            {
                $query .= ', r.contenu AS texte FROM wiki_pages AS w LEFT JOIN wiki_revisions AS r ON (w.id = r.id_page AND w.revision = r.revision) ';
            }
            else
            {
                $query .= '\'\' AS texte FROM wiki_pages AS w ';
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

            $allowed_fields = ['id', 'uri', 'titre', 'date', 'date_creation', 'date_modification',
                'parent', 'rubrique', 'revision', 'points', 'recherche', 'texte', 'age'];
            $search = $search_rank = false;

            foreach ($loopCriterias as $criteria_id => $criteria)
            {
                if (isset($criteria['field']))
                {
                    if (!in_array($criteria['field'], $allowed_fields))
                    {
                        throw new \KD2\MiniSkelMarkupException("Critère '".$criteria['field']."' invalide pour la boucle ".$loopName." de type ".$loopType.".");
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
                        if ($criteria['action'] != \KD2\MiniSkel::ACTION_ORDER_BY)
                        {
                            throw new \KD2\MiniSkelMarkupException("Le critère 'points' n\'est pas valide dans ce contexte.");
                        }

                        $search_rank = true;
                    }
                    elseif ($criteria['field'] == 'age')
                    {
                        $criteria['field'] = 'julianday() - julianday(date_creation)';
                        $criteria['value'] = (int)$criteria['value'];
                    }
                }

                switch ($criteria['action'])
                {
                    case \KD2\MiniSkel::ACTION_ORDER_BY:
                        if (!$order)
                            $order = 'ORDER BY '.$criteria['field'].'';
                        else
                            $order .= ', '.$criteria['field'].'';
                        break;
                    case \KD2\MiniSkel::ACTION_ORDER_DESC:
                        if ($order)
                            $order .= ' DESC';
                        break;
                    case \KD2\MiniSkel::ACTION_LIMIT:
                        $begin = $criteria['begin'];
                        $limit = $criteria['number'];
                        break;
                    case \KD2\MiniSkel::ACTION_MATCH_FIELD_BY_VALUE:
                        $where .= ' AND '.$criteria['field'].' '.$criteria['comparison'].' ?';
                        $query_args[] = $criteria['value'];
                        break;
                    case \KD2\MiniSkel::ACTION_MATCH_FIELD:
                    {
                        if ($criteria['field'] == 'recherche')
                        {
                            $query = 'SELECT w.*, r.contenu AS texte, rank(matchinfo(wiki_recherche), 0, 1.0, 1.0) AS points FROM wiki_pages AS w INNER JOIN wiki_recherche AS r ON (w.id = r.id) ';
                            $where .= ' AND wiki_recherche MATCH ?';
                            $search = true;
                        }
                        else
                        {
                            $where .= ' AND '.$criteria['field'].' = ?';

                            if ($criteria['field'] == 'parent')
                            {
                                $criteria['field'] = 'id';
                            }
                        }
                        
                        $query_args[] = ['$this->getVariable(\'' . $criteria['field'] . '\')'];
                        break;
                    }
                    default:
                        break;
                }
            }

            if ($search_rank && !$search)
            {
                throw new \KD2\MiniSkelMarkupException("Le critère par points n'est possible que dans les boucles de recherche.");
            }

            if (trim($loopContent))
            {
                $loop_start .= '$row[\'url\'] = WWW_URL . $row[\'uri\']; ';
            }

            $query .= $where . ' ' . $order;

            if (!$limit || $limit > 100)
                $limit = 100;

            if ($limit)
            {
                $query .= ' LIMIT ';

                if (is_numeric($begin))
                {
                    $query .= (int) $begin;
                }
                else
                {
                    $query .= '?';
                    $query_args[] = ['\'.$this->variables[\'debut_liste\'].\''];
                }
                
                $query .= ','.(int)$limit;
            }
        }
        else if ($loopType == 'documents' || $loopType == 'images' || $loopType == 'fichiers')
        {
            $where = $order = '';
            $limit = $begin = 0;

            $link = false;

            $query = 'SELECT f.*, fc.hash, fc.taille, strftime(\'%s\', f.datetime) AS date ';
            $query.= ' FROM fichiers AS f INNER JOIN fichiers_contenu AS fc ON fc.id = f.id_contenu ';
            $query.= ' INNER JOIN fichiers_wiki_pages AS fwp ON fwp.fichier = f.id ';
            $query.= ' INNER JOIN wiki_pages AS w ON w.id = fwp.id AND w.droit_lecture = -1 ';
            $where = 'WHERE 1 ';

            $allowed_fields = ['id', 'nom', 'type', 'date', 'image', 'hash', 'taille',
                'parent', 'page', 'rubrique', 'article', 'sauf_mention'];

            if ($loopType == 'images')
            {
                $where .= ' AND image = 1 ';
            }
            else if ($loopType == 'documents')
            {
                $where .= ' AND image = 0 ';
            }

            foreach ($loopCriterias as $criteria_id => $criteria)
            {
                if (isset($criteria['field']))
                {
                    if (!in_array($criteria['field'], $allowed_fields))
                    {
                        throw new \KD2\MiniSkelMarkupException("Critère '".$criteria['field']."' invalide pour la boucle ".$loopName." de type ".$loopType.".");
                    }
                    elseif ($criteria['field'] == 'rubrique' || $criteria['field'] == 'page' 
                        || $criteria['field'] == 'article' || $criteria['field'] == 'parent')
                    {
                        $criteria['field'] = 'w.id';
                    }
                    elseif ($criteria['field'] == 'date')
                    {
                        $criteria['field'] = 'datetime';
                    }
                }

                switch ($criteria['action'])
                {
                    case \KD2\MiniSkel::ACTION_ORDER_BY:
                        if (!$order)
                            $order = 'ORDER BY '.$criteria['field'].'';
                        else
                            $order .= ', '.$criteria['field'].'';
                        break;
                    case \KD2\MiniSkel::ACTION_ORDER_DESC:
                        if ($order)
                            $order .= ' DESC';
                        break;
                    case \KD2\MiniSkel::ACTION_LIMIT:
                        $begin = $criteria['begin'];
                        $limit = $criteria['number'];
                        break;
                    case \KD2\MiniSkel::ACTION_MATCH_FIELD_BY_VALUE:
                        $where .= ' AND '.$criteria['field'].' '.$criteria['comparison'].' ?';
                        $query_args[] = $criteria['value'];
                        break;
                    case \KD2\MiniSkel::ACTION_MATCH_FIELD:
                    {
                        if ($criteria['field'] == 'sauf_mention')
                        {
                            $where .= " AND f.id NOT IN (:php_implode) ";
                            $query_args[':php_implode'] = '\'.implode(",", Fichiers::listFilesUsedInText($this->getVariable(\'texte\'))).\'';
                            break;
                        }

                        $where .= ' AND '.$criteria['field'].' = ?';

                        if ($criteria['field'] == 'w.id')
                        {
                            $criteria['field'] = 'id';
                        }
                        
                        $query_args[] = ['$this->getVariable(\'' . $criteria['field'] . '\')'];
                        break;
                    }
                    default:
                        break;
                }
            }

            if (trim($loopContent))
            {
                $loop_start .= '$row[\'url\'] = Fichiers::_getURL($row[\'id\'], $row[\'nom\']); ';
                $loop_start .= '$row[\'miniature\'] = $row[\'image\'] ? Fichiers::_getURL($row[\'id\'], $row[\'nom\'], 200) : \'\'; ';
                $loop_start .= '$row[\'moyenne\'] = $row[\'image\'] ? Fichiers::_getURL($row[\'id\'], $row[\'nom\'], 200) : \'\'; ';
            }

            $query .= $where . ' ' . $order;

            if (!$limit || $limit > 100)
                $limit = 100;

            if ($limit)
            {
                $query .= ' LIMIT ';

                if (is_numeric($begin))
                {
                    $query .= (int) $begin;
                }
                else
                {
                    $query .= '?';
                    $query_args[] = ['\'.$this->variables[\'debut_liste\'].\''];
                }
                
                $query .= ','.(int)$limit;
            }
        }
        else
        {
            $params = [
                'loopName'  =>  $loopName,
                'loopType'  =>  $loopType,
                'loopCriterias' =>  $loopCriterias,
                'loopContent'   =>  $loopContent,
                'preContent'    =>  $preContent,
                'postContent'   =>  $postContent,
                'altContent'    =>  $altContent,
            ];

            $callback_return = [
                'query'         =>  &$query,
                'query_args'    =>  &$query_args,
                'loop_start'    =>  &$loop_start,
            ];

            // Appel du plugin lié à cette boucle, si ça existe
            $return = Plugin::fireSignal('boucle.' . $loopType, $params, $callback_return);

            // Si le retour est du texte on le traite comme tel
            if (is_string($return))
            {
                return $return;
            }
            // Sinon si ce n'est pas true
            elseif (!$return)
            {
                throw new \KD2\MiniSkelMarkupException("Le type de boucle '".$loopType."' est inconnu.");
            }

            if (empty($query))
            {
                $query = 'SELECT 0 LIMIT 0;';
            }
        }

        try {
            // Sécurité anti injection, à la compilation seulement
            $statement = $db->prepare($query);
        }
        catch (\Exception $e)
        {
            throw new \KD2\MiniSkelMarkupException("Erreur SQL dans la requête : ".$e->getMessage() . "\n " . $query);
        }
        
        if (!$statement->readOnly())
        {
            throw new \KD2\MiniSkelMarkupException("Requête en écriture illégale: ".$query);
        }

        $hash = sha1(uniqid(mt_rand(), true));
        $out = new Squelette_Snippet();
        $out->append(1, '$parent_hash = $this->current[\'_self_hash\'];');
        $out->append(1, '$this->parent =& $parent_hash ? $this->_vars[$parent_hash] : null;');

        if (!empty($search))
        {
            $out->append(1, 'if (trim($this->getVariable(\'recherche\'))) { ');
        }

        $query = var_export($query, true);

        foreach ($query_args as $k=>$arg)
        {
            if (substr($k, 0, 4) == ':php')
            {
                $query = str_replace($k, $arg, $query);
                unset($query_args[$k]);
            }
        }

        $out->append(1, '$statement = $db->prepare('.$query.'); ');

        foreach ($query_args as $k=>$arg)
        {
            $out->append(1, '$value = ' . (is_array($arg) ? $arg[0] : var_export($arg, true)) . ';');
            $out->append(1, '$statement->bindValue(' . ($k+1) . ', $value, $db->getArgType($value));');
        }

        $out->append(1, '$result_'.$hash.' = $statement->execute(); ');
        $out->append(1, '$nb_rows = $db->countRows($result_'.$hash.'); ');

        if (!empty($search))
        {
            $out->append(1, '} else { $result_'.$hash.' = false; $nb_rows = 0; }');
        }

        $out->append(1, '$this->_vars[\''.$hash.'\'] = [\'_self_hash\' => \''.$hash.'\', \'_parent_hash\' => $parent_hash, \'total_boucle\' => $nb_rows, \'compteur_boucle\' => 0];');
        $out->append(1, '$this->current =& $this->_vars[\''.$hash.'\']; ');
        $out->append(1, 'if ($nb_rows > 0):');

        if ($preContent)
        {
            $out->append(2, $this->parse($preContent, $loopName, self::PRE_CONTENT));
        }

        $out->append(1, 'while ($row = $result_'.$hash.'->fetchArray(SQLITE3_ASSOC)): ');
        $out->append(1, '$this->_vars[\''.$hash.'\'][\'compteur_boucle\'] += 1; ');
        $out->append(1, $loop_start);
        $out->append(1, '$this->_vars[\''.$hash.'\'] = array_merge($this->_vars[\''.$hash.'\'], $row); ');

        $out->append(2, $this->parseVariables($loopContent));

        $out->append(1, 'endwhile;');

        // we put the post-content after the loop content
        if ($postContent)
        {
            $out->append(2, $this->parse($postContent, $loopName, self::POST_CONTENT));
        }

        if ($altContent)
        {
            $out->append(1, 'else:');
            $out->append(2, $this->parse($altContent, $loopName, self::ALT_CONTENT));
        }

        $out->append(1, 'endif; ');
        $out->append(1, '$parent_hash = $this->_vars[\''.$hash.'\'][\'_parent_hash\']; ');
        $out->append(1, 'unset($result_'.$hash.', $nb_rows, $this->_vars[\''.$hash.'\']); ');
        $out->append(1, 'if ($parent_hash) { $this->current =& $this->_vars[$parent_hash]; $parent_hash = $this->current[\'_parent_hash\']; } ');
        $out->append(1, 'else { $this->current = null; }');
        $out->append(1, '$this->parent =& $parent_hash ? $this->_vars[$_parent_hash] : null;');

        return $out;
    }

    public function fetch($template, $no_display = false)
    {
        $this->currentTemplate = $template;

        $path = file_exists(DATA_ROOT . '/www/squelettes/' . $template)
            ? DATA_ROOT . '/www/squelettes/' . $template
            : ROOT . '/www/squelettes-dist/' . $template;

        $tpl_id = basename(dirname($path)) . '/' . $template;

        if (!self::compile_check($tpl_id, $path))
        {
            if (!file_exists($path))
            {
                throw new \KD2\MiniSkelMarkupException('Le squelette "'.$tpl_id.'" n\'existe pas.');
            }

            $content = file_get_contents($path);
            $content = strtr($content, ['<?php' => '&lt;?php', '<?' => '<?php echo \'<?\'; ?>']);
            $out = new Squelette_Snippet(2, $this->parse($content));
            $out->prepend(1, '/* '.$tpl_id.' */ '.
                'namespace Garradin; $db = DB::getInstance(); '.
                'if ($this->parent) $parent_hash = $this->parent[\'_self_hash\']; '. // For included files
                'else $parent_hash = false;');

            if (!$no_display)
            {
                self::compile_store($tpl_id, $out);
            }
        }


        if (!$no_display)
        {
            require self::compile_get_path($tpl_id);
        }
        else
        {
            eval($tpl_id);
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
        else
        {
            // WWW_URI inclus toujours le slash final, mais on veut le conserver ici
            $uri = substr($uri, strlen(WWW_URI) - 1);
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
        elseif ($uri == '/favicon.ico')
        {
            header('Location: ' . WWW_URI . 'admin/static/icon.png');
            exit;
        }
        elseif (substr($uri, -1) == '/')
        {
            $skel = 'rubrique.html';
            $_GET['uri'] = $_REQUEST['uri'] = substr($uri, 1, -1);
        }
        elseif (preg_match('!^/admin/!', $uri))
        {
            throw new UserException('Cette page n\'existe pas.');
        }
        else
        {
            $_GET['uri'] = $_REQUEST['uri'] = substr($uri, 1);

            if (preg_match('!^[\w\d_-]+$!i', $_GET['uri'])
                && file_exists(DATA_ROOT . '/www/squelettes/' . strtolower($_GET['uri']) . '.html'))
            {
                $skel = strtolower($_GET['uri']) . '.html';
            }
            else
            {
                $skel = 'article.html';
            }
        }

        $this->display($skel);
    }

    static private function compile_get_path($path)
    {
        $hash = sha1($path);
        return CACHE_ROOT . '/compiled/s_' . $hash . '.php';
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
        if (isset($this->current[$var]))
        {
            return $this->current[$var];
        }
        elseif (isset($this->parent[$var]))
        {
            return $this->parent[$var];
        }
        elseif (isset($this->variables[$var]))
        {
            return $this->variables[$var];
        }
        elseif (isset($_REQUEST[$var]))
        {
            return $_REQUEST[$var];
        }
        else
        {
            return null;
        }
    }

    static public function getSource($template)
    {
        if (!preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $template))
            return false;

        $path = file_exists(DATA_ROOT . '/www/squelettes/' . $template)
            ? DATA_ROOT . '/www/squelettes/' . $template
            : ROOT . '/www/squelettes-dist/' . $template;

        if (!file_exists($path))
            return false;

        return file_get_contents($path);
    }

    static public function editSource($template, $content)
    {
        if (!preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $template))
            return false;

        $path = DATA_ROOT . '/www/squelettes/' . $template;

        return file_put_contents($path, $content);
    }

    static public function resetSource($template)
    {
        if (!preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $template))
            return false;

        if (file_exists(DATA_ROOT . '/www/squelettes/' . $template))
        {
            return unlink(DATA_ROOT . '/www/squelettes/' . $template);
        }

        return false;
    }

    static public function listSources()
    {
        if (!file_exists(DATA_ROOT . '/www/squelettes'))
        {
            mkdir(DATA_ROOT . '/www/squelettes', 0775, true);
        }

        $sources = [];

        $dir = dir(ROOT . '/www/squelettes-dist');

        while ($file = $dir->read())
        {
            if ($file[0] == '.')
                continue;

            if (!preg_match('/\.(?:css|x?html?|atom|rss|xml|svg|txt)$/i', $file))
                continue;

            $sources[$file] = false;
        }

        $dir->close();

        $dir = dir(DATA_ROOT . '/www/squelettes');

        while ($file = $dir->read())
        {
            if ($file[0] == '.')
                continue;

            if (!preg_match('/\.(?:css|x?html?|atom|rss|xml|svg|txt)$/i', $file))
                continue;

            $sources[$file] = [
                // Est-ce que le fichier fait partie de la distribution de Garradin?
                // (Si non pas possible de faire un reset)
                'dist'  =>  array_key_exists($file, $sources),
                'mtime' =>  filemtime($dir->path.'/'.$file),
            ];
        }

        $dir->close();

        ksort($sources);

        return $sources;
    }

}
