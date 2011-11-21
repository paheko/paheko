<?php

class utils
{
    static protected $country_list = null;

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
        return WWW_URL . ($uri ? substr($uri, 1) : '');
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

        $_SESSION['csrf'][$key] = sha1($key . self::$random_hash . time());
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

        return true;
    }

    static public function CSRF_field_name($key)
    {
        return 'gecko/'.base64_encode(sha1($key, true));
    }

    static public function generatePassword($length, $chars='abcdefghijklmnopqrstuvwxyz1234567890')
    {
        $string = '';
        for ($i = 0; $i < $length; $i++)
        {
            $pos = rand(0, strlen($chars)-1);
            $string .= $chars{$pos};
        }
        return $string;
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
}

?>