<?php

class utils
{
    static protected $country_list = null;

    static protected $g2x = null;

    static private $french_date_names = array(
        'January'=>'Janvier', 'February'=>'Février', 'March'=>'Mars', 'April'=>'Avril', 'May'=>'Mai',
        'June'=>'Juin', 'July'=>'Juillet', 'August'=>'Août', 'September'=>'Septembre', 'October'=>'Octobre',
        'November'=>'Novembre', 'December'=>'Décembre', 'Monday'=>'Lundi', 'Tuesday'=>'Mardi', 'Wednesday'=>'Mercredi',
        'Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi','Sunday'=>'Dimanche',
        'Feb'=>'Fév','Apr'=>'Avr','May'=>'Mai','Jun'=>'Juin', 'Jul'=>'Juil','Aug'=>'Aout','Dec'=>'Déc',
        'Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mer','Thu'=>'Jeu','Fri'=>'Ven','Sat'=>'Sam','Sun'=>'Dim');

    static public function strftime_fr($format=null, $ts=null)
    {
        if (is_null($format))
        {
            $format = '%d/%m/%Y à %H:%M';
        }

        $date = strftime($format, $ts);
        $date = strtr($date, self::$french_date_names);
        $date = strtolower($date);
        return $date;
    }

    static public function date_fr($format=null, $ts=null)
    {
        if (is_null($format))
        {
            $format = 'd/m/Y à H:i';
        }

        $date = date($format, $ts);
        $date = strtr($date, self::$french_date_names);
        $date = strtolower($date);
        return $date;
    }

    static public function makeTimestampFromForm($d)
    {
        return mktime($d['h'], $d['min'], 0, $d['m'], $d['d'], $d['y']);
    }

    static public function getRequestURI()
    {
        if (!empty($_SERVER['REQUEST_URI']))
            return $_SERVER['REQUEST_URI'];
        else
            return false;
    }

    static public function getSelfURL()
    {
        $uri = self::getRequestUri();

        if (strpos($uri, WWW_URI) === 0)
        {
            $uri = substr($uri, strlen(WWW_URI));
        }

        return WWW_URL . $uri;
    }

    static public function disableHttpCaching()
    {
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header('Pragma: no-cache');
    }


    public static function redirect($destination=false, $exit=true)
    {
        if (empty($destination) || !preg_match('/^https?:\/\//', $destination))
        {
            if (empty($destination))
                $destination = WWW_URL;
            else
                $destination = WWW_URL . preg_replace('/^\//', '', $destination);
        }

        if (headers_sent())
        {
            echo
              '<html>'.
              ' <head>' .
              '  <script type="text/javascript">' .
              '    document.location = "' . htmlspecialchars($destination, ENT_QUOTES, 'UTF-8', false) . '";' .
              '  </script>' .
              ' </head>'.
              ' <body>'.
              '   <div>'.
              '     <a href="' . htmlspecialchars($destination, ENT_QUOTES, 'UTF-8', false) . '">Cliquez ici pour continuer...</a>'.
              '   </div>'.
              ' </body>'.
              '</html>';

            if ($exit)
              exit();

            return true;
        }

        header("Location: " . $destination);

        if ($exit)
          exit();
    }


    static protected function _sessionStart($force = false)
    {
        if (!isset($_SESSION) && ($force || isset($_COOKIE[session_name()])))
        {
            session_start();
        }
        return true;
    }

    static public function CSRF_create($key)
    {
        self::_sessionStart(true);

        if (!isset($_SESSION['csrf']))
        {
            $_SESSION['csrf'] = array();
        }

        $_SESSION['csrf'][$key] = sha1($key . uniqid($key, true) . time());
        return $_SESSION['csrf'][$key];
    }

    static public function CSRF_check($key, $hash=null)
    {
        self::_sessionStart();

        if (is_null($hash))
        {
            $name = self::CSRF_field_name($key);

            if (!isset($_POST[$name]))
                return false;

            $hash = $_POST[$name];
        }

        if (empty($_SESSION['csrf'][$key]))
            return false;

        if ($_SESSION['csrf'][$key] != $hash)
            return false;

        unset($_SESSION['csrf'][$key]);

        return true;
    }

    static public function CSRF_field_name($key)
    {
        return 'gecko/'.base64_encode(sha1($key, true));
    }

    static public function generatePassword($length, $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
    {
        $string = '';
        for ($i = 0; $i < $length; $i++)
        {
            $pos = rand(0, strlen($chars)-1);
            $string .= $chars[$pos];
        }
        return $string;
    }

    static public function post($key)
    {
        return isset($_POST[$key]) ? $_POST[$key] : '';
    }

    static public function get($key)
    {
        return isset($_GET[$key]) ? $_GET[$key] : '';
    }

    static public function getIP()
    {
        if (!empty($_SERVER['REMOTE_ADDR']))
            return $_SERVER['REMOTE_ADDR'];
        return '';
    }

    static public function &getCountryList()
    {
        if (is_null(self::$country_list))
        {
            require_once GARRADIN_ROOT . '/include/countries_fr.php';
            self::$country_list = $countries;
        }

        return self::$country_list;
    }

    static public function getCountryName($code)
    {
        $list = self::getCountryList();

        if (!isset($list[$code]))
            return false;

        return $list[$code];
    }

    /**
     * Génération pagination à partir de la page courante ($current),
     * du nombre d'items total ($total), et du nombre d'items par page ($bypage).
     * $listLength représente la longueur d'items de la pagination à génerer
     *
     * @param int $current
     * @param int $total
     * @param int $bypage
     * @param int $listLength
     * @param bool $showLast Toggle l'affichage du dernier élément de la pagination
     * @return array
     */
    public static function getGenericPagination($current, $total, $bypage, $listLength=11, $showLast = true)
    {
        if ($total <= $bypage)
            return false;

        $total = ceil($total / $bypage);

        if ($total < $current)
            return false;

        $length = ($listLength / 2);

        $begin = $current - ceil($length);
        if ($begin < 1)
        {
            $begin = 1;
        }

        $end = $begin + $listLength;
        if($end > $total)
        {
            $begin -= ($end - $total);
            $end = $total;
        }
        if ($begin < 1)
        {
            $begin = 1;
        }
        if($end==($total-1)) {
            $end = $total;
        }
        if($begin == 2) {
            $begin = 1;
        }
        $out = array();

        if ($current > 1) {
            $out[] = array('id' => $current - 1, 'label' =>  '« ' . 'Page précédente', 'class' => 'prev', 'accesskey' => 'a');
        }

        if ($begin > 1) {
            $out[] = array('id' => 1, 'label' => '1 ...', 'class' => 'first');
        }

        for ($i = $begin; $i <= $end; $i++)
        {
            $out[] = array('id' => $i, 'label' => $i, 'class' => ($i == $current) ? 'current' : '');
        }

        if ($showLast && $end < $total) {
            $out[] = array('id' => $total, 'label' => '... ' . $total, 'class' => 'last');
        }

        if ($current < $total) {
            $out[] = array('id' => $current + 1, 'label' => 'Page suivante' . ' »', 'class' => 'next', 'accesskey' => 'z');
        }

        return $out;
    }

    static public function transliterateToAscii($str, $charset='utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

        return $str;
    }

    static public function htmlLinksOnUrls($str)
    {
        preg_match_all('!(^|[\s>])((ftp|http|https)://([^\s<]+))!u', $str, $match, PREG_SET_ORDER);

        foreach ($match as &$m)
        {
            $text = ($m[3] == 'http') ? $m[4] : $m[2];
            $text = isset($text[51]) ? substr($text, 0, 50) . '...' : $text;
            $str = str_replace($m[0], $m[1].'<a href="'.$m[2].'" onclick="window.open(this.href); return false;">'.$text.'</a>', $str);
        }

        return $str;
    }

    static public function htmlGarbage2xhtml($str)
    {
        if (!self::$g2x)
        {
            require_once GARRADIN_ROOT . '/include/lib.garbage2xhtml.php';
            self::$g2x = new garbage2xhtml;
            self::$g2x->core_attributes = array('class', 'id', 'title');
        }

        return self::$g2x->process($str);
    }

    static public function htmlSpip($str)
    {
        // Intertitres
        $str = preg_replace('!(^|[^\\\\])\{{3}(\V*)\}{3}!', '$1<h3>$2</h3>', $str);

        // Gras
        $str = preg_replace('!(^|[^\\\\])\{{2}(\V*)\}{2}!', '$1<strong>$2</strong>', $str);

        // Italique
        $str = preg_replace('!(^|[^\\\\])\{(\V*)\}!', '$1<em>$2</em>', $str);

        // Espaces typograhiques
        $str = preg_replace('/\h*([?!;:»])(\s+|$)/u', '&nbsp;$1$2', $str);
        $str = preg_replace('/(^|\s+)([«])\h*/u', '$1$2&nbsp;', $str);

        // Liens
        $str = preg_replace('!(^|[^\\\\])\[([^-]+)->([^\]]+)\]!', '$1<a href="$3">$2</a>', $str);
        $str = preg_replace('!(^|[^\\\\])\[([^\]]+)\]!', '$1<a href="$2">$2</a>', $str);

        return $str;
    }
}

?>