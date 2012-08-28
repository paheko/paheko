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

    static public function getSelfURL($no_qs = false)
    {
        $uri = self::getRequestUri();

        if (strpos($uri, WWW_URI) === 0)
        {
            $uri = substr($uri, strlen(WWW_URI));
        }

        if ($no_qs && (strpos($uri, '?') !== false))
        {
            $uri = substr($uri, 0, strpos($uri, '?'));
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
            require_once GARRADIN_ROOT . '/include/libs/countries/countries_fr.php';
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

    static public function transliterateToAscii($str, $charset='UTF-8')
    {
        // Don't process empty strings
        if (!trim($str))
            return $str;

        // We only process non-ascii strings
        if (preg_match('!^[[:ascii:]]+$!', $str))
            return $str;

        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'

        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
        $str = preg_replace('![^[:ascii:]]+!', '', $str);

        return $str;
    }

    static public function htmlLinksOnUrls($str)
    {
        return preg_replace_callback('!(?<=\s|^)((?:(ftp|https?|file|ed2k|ircs?)://|(magnet|mailto|data|tel|fax|geo|sips?|xmpp):)([^\s<]+))!',
            function ($match) {
                $proto = $match[2] ?: $match[3];
                $text = ($proto == 'http' || $proto == 'mailto') ? $match[4] : $match[1];
                return '<a class="'.$proto.'" href="'.htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</a>';
            }, $str);
    }

    static public function htmlGarbage2xhtml($str)
    {
        if (!self::$g2x)
        {
            require_once GARRADIN_ROOT . '/include/libs/garbage2xhtml/lib.garbage2xhtml.php';
            self::$g2x = new garbage2xhtml;
            self::$g2x->core_attributes = array('class', 'id', 'title');
        }

        return self::$g2x->process($str);
    }

    static public function htmlSpip($str)
    {
        // Intertitres
        $str = preg_replace('/(?<!\\\\)\{{3}(\V*)\}{3}/', '<h3>$1</h3>', $str);

        // Gras
        $str = preg_replace('/(?<!\\\\)\{{2}(\V*)\}{2}/', '<strong>$1</strong>', $str);

        // Italique
        $str = preg_replace('/(?<!\\\\)\{(\V*)\}/', '<em>$1</em>', $str);

        // Espaces typograhiques
        $str = preg_replace('/\h*([?!;:»])(\s+|$)/u', '&nbsp;$1$2', $str);
        $str = preg_replace('/(^|\s+)([«])\h*/u', '$1$2&nbsp;', $str);

        // Liens
        $str = preg_replace('/(?<!\\\\)\[([^-]+)->([^\]]+)\]/', '<a href="$2">$1</a>', $str);
        $str = preg_replace('/(?<!\\\\)\[([^\]]+)\]/', '<a href="$1">$1</a>', $str);

        return $str;
    }

    static public function mail($to, $subject, $content, $additional_headers = array())
    {
        // Création du contenu du message
        $content = wordwrap($content);
        $content = trim($content);

        $content = preg_replace("#(?<!\r)\n#si", "\r\n", $content);

        // Construction des entêtes
        $headers = '';

        if (empty($additional_headers['From']))
        {
            $config = Garradin_Config::getInstance();
            $additional_headers['From'] = '"NE PAS REPONDRE" <'.$config->get('email_envoi_automatique').'>';
        }

        $additional_headers['MIME-Version'] = '1.0';
        $additional_headers['Content-type'] = 'text/plain; charset=UTF-8';
        $additional_headers['Return-Path'] = $config->get('email_envoi_automatique');

        foreach ($additional_headers as $name=>$value)
        {
            $headers .= $name . ': '.$value."\r\n";
        }

        $headers = preg_replace("#(?<!\r)\n#si", "\r\n", $headers);

        $subject = '=?UTF-8?B?'.base64_encode($subject).'?=';

        if (is_array($to))
        {
            foreach ($to as $t)
            {
                return mail($t, $suject, $content, $headers);
            }
        }
        else
        {
            return mail($to, $subject, $content, $headers);
        }
    }

    static public function clearCaches()
    {
        $path = GARRADIN_ROOT . '/cache/compiled';
        $dir = dir($path);

        while ($file = $dir->read())
        {
            if ($file[0] != '.')
            {
                unlink($path . '/' . $file);
            }
        }

        $dir->close();
        return true;
    }

    static public function suggestPassword()
    {
        require_once GARRADIN_ROOT . '/include/libs/passphrase/lib.passphrase.french.php';
        return Passphrase::generate();
    }

    static public function checkIBAN($iban)
    {
        $iban = substr($iban, 4) . substr($iban, 0, 4);
        $iban = str_replace(range('A', 'Z'), range(10, 35), $iban);
        return (bcmod($iban, 97) == 1);
    }

    static public function IBAN_RIB($iban)
    {
        if (substr($iban, 0, 2) != 'FR')
        {
            return '';
        }

        return substr($iban, 4, 5) // Code banque
            . ' ' . substr($iban, 4+5, 5) // Code guichet
            . ' ' . substr($iban, 4+5+5, -2) // Numéro de compte
            . ' ' . substr($iban, -2); // Clé RIB
    }

    static public function checkBIC($bic)
    {
        return preg_match('!^[A-Z]{4}[A-Z]{2}[1-9A-Z]{2}(?:[A-Z\d]{3})?$!', $bic);
    }

    function json_readable_encode($in, $indent = 0, Closure $_escape = null)
    {
        if (__CLASS__ && isset($this))
        {
            $_myself = array($this, __FUNCTION__);
        }
        elseif (__CLASS__)
        {
            $_myself = array('self', __FUNCTION__);
        }
        else
        {
            $_myself = __FUNCTION__;
        }

        if (is_null($_escape))
        {
            $_escape = function ($str)
            {
                return str_replace(
                    array('\\', '"', "\n", "\r", "\b", "\f", "\t", '/', '\\\\u'),
                    array('\\\\', '\\"', "\\n", "\\r", "\\b", "\\f", "\\t", '\\/', '\\u'),
                    $str);
            };
        }

        $out = '';

        foreach ($in as $key=>$value)
        {
            $out .= str_repeat("\t", $indent + 1);
            $out .= "\"".$_escape((string)$key)."\": ";

            if (is_object($value) || is_array($value))
            {
                $out .= "\n";
                $out .= call_user_func($_myself, $value, $indent + 1, $_escape);
            }
            elseif (is_bool($value))
            {
                $out .= $value ? 'true' : 'false';
            }
            elseif (is_null($value))
            {
                $out .= 'null';
            }
            elseif (is_string($value))
            {
                $out .= "\"" . $_escape($value) ."\"";
            }
            else
            {
                $out .= $value;
            }

            $out .= ",\n";
        }

        if (!empty($out))
        {
            $out = substr($out, 0, -2);
        }

        $out = str_repeat("\t", $indent) . "{\n" . $out;
        $out .= "\n" . str_repeat("\t", $indent) . "}";

        return $out;
    }

}

?>