<?php

namespace Garradin;

class Squelette_Filtres
{
    static private $g2x = null;
    static private $alt = [];

    static public $filtres_php = [
        'strtolower',
        'strtoupper',
        'ucfirst',
        'ucwords',
        'str_rot13',
        'str_shuffle',
        'htmlentities',
        'htmlspecialchars',
        'trim',
        'ltrim',
        'rtrim',
        'lcfirst',
        'md5',
        'sha1',
        'metaphone',
        'nl2br',
        'soundex',
        'str_split',
        'str_word_count',
        'strrev',
        'strlen',
        'wordwrap',
        'strip_tags' => 'supprimer_tags',
        'var_dump',
    ];

    static public $filtres_alias = [
        '!='    =>  'different_de',
        '=='    =>  'egal_a',
        '?'     =>  'choixsivide',
        '>'     =>  'superieur_a',
        '>='    =>  'superieur_ou_egal_a',
        '<'     =>  'inferieur_a',
        '<='    =>  'inferieur_ou_egal_a',
        'yes'   =>  'oui',
        'no'    =>  'non',
        'and'   =>  'et',
        'or'    =>  'ou',
        'xor'   =>  'xou',
    ];

    static public $desactiver_defaut = [
        'formatter_texte',
        'entites_html',
        'proteger_contact',
        'echapper_xml',
    ];

    static public function date_en_francais($date)
    {
        return ucfirst(strtolower(Utils::strftime_fr('%A %e %B %Y', $date)));
    }

    static public function heure_en_francais($date)
    {
        return Utils::strftime_fr('%Hh%I', $date);
    }

    static public function mois_en_francais($date)
    {
        return Utils::strftime_fr('%B %Y', $date);
    }

    static public function date_perso($date, $format)
    {
        return Utils::strftime_fr($format, $date);
    }

    static public function date_intelligente($date)
    {
        if (date('Ymd', $date) == date('Ymd'))
            return 'Aujourd\'hui, '.date('H\hi', $date);
        elseif (date('Ymd', $date) == date('Ymd', strtotime('yesterday')))
            return 'Hier, '.date('H\hi', $date);
        elseif (date('Y', $date) == date('Y'))
            return strtolower(Utils::strftime_fr('%e %B, %Hh%M', $date));
        else
            return strtolower(Utils::strftime_fr('%e %B %Y', $date));
    }

    static public function date_atom($date)
    {
        return date(DATE_ATOM, $date);
    }

    static public function alterner($v, $name, $valeur1, $valeur2)
    {
        if (!array_key_exists($name, self::$alt))
        {
            self::$alt[$name] = 0;
        }

        if (self::$alt[$name]++ % 2 == 0)
            return $valeur1;
        else
            return $valeur2;
    }

    static public function proteger_contact($contact)
    {
        if (!trim($contact))
            return '';

        if (strpos($contact, '@'))
            return '<span style="unicode-bidi:bidi-override;direction: rtl;">'.htmlspecialchars(strrev($contact), ENT_QUOTES, 'UTF-8').'</span>';
        else
            return '<a href="'.htmlspecialchars($contact, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($contact, ENT_QUOTES, 'UTF-8').'</a>';
    }

    static public function entites_html($texte)
    {
        return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
    }

    static public function echapper_xml($texte)
    {
        return str_replace('&#039;', '&apos;', htmlspecialchars($texte, ENT_QUOTES, 'UTF-8'));
    }

    static public function formatter_texte($texte)
    {
        $texte = Utils::SkrivToHTML($texte);
        $texte = self::typo_fr($texte);

        return $texte;
    }

    static public function typo_fr($str, $html = true)
    {
        $space = $html ? '&nbsp;' : ' ';
        $str = preg_replace('/(?:[\h]|&nbsp;)*([?!:»])(\s+|$)/u', $space.'\\1\\2', $str);
        $str = preg_replace('/(^|\s+)([«])(?:[\h]|&nbsp;)*/u', '\\1\\2'.$space, $str);
        return $str;
    }

    static public function pagination($total, $debut, $par_page)
    {
        $max_page = ceil($total / $par_page);
        $current = ($debut > 0) ? ceil($debut / $par_page) + 1 : 1;
        $out = '';

        if ($current > 1)
        {
            $out .= '<a href="./'.($current > 2 ? '+' . ($debut - $par_page) : '').'">&laquo; Page pr&eacute;c&eacute;dente</a> - ';
        }

        for ($i = 1; $i <= $max_page; $i++)
        {
            $link = ($i == 1) ? './' : './+' . (($i - 1) * $par_page);

            if ($i == $current)
                $out .= '<strong>'.$i.'</strong> - ';
            else
                $out .= '<a href="'.$link.'">'.$i.'</a> - ';
        }

        if ($current < $max_page)
        {
            $out .= '<a href="./+'.($debut + $par_page).'">Page suivante &raquo;</a>';
        }
        else
        {
            $out = substr($out, 0, -3);
        }

        return $out;
    }

    // Compatibilité SPIP

    static public function egal_a($value, $test)
    {
        if ($value == $test)
            return true;
        else
            return false;
    }

    static public function different_de($value, $test)
    {
        if ($value != $test)
            return true;
        else
            return false;
    }

    // disponible aussi avec : | ?{sioui, sinon}
    static public function choixsivide($value, $un, $deux = '')
    {
        if (empty($value) || !trim($value))
            return $deux;
        else
            return $un;
    }

    static public function sinon($value, $sinon = '')
    {
        if ($value)
            return $value;
        else
            return $sinon;
    }

    static public function choixsiegal($value, $test, $un, $deux)
    {
        return ($value == $test) ? $un : $deux;
    }

    static public function supprimer_tags($value, $replace = '')
    {
        return preg_replace('!<[^>]*>!', $replace, $value);
    }

    static public function supprimer_spip($value)
    {
        $value = preg_replace('!\[([^\]]+)(?:->[^\]]*)?\]!U', '$1', $value);
        $value = preg_replace('!\{+([^\}]*)\}+!', '$1', $value);
        return $value;
    }

    static public function couper($texte, $taille, $etc = ' (...)')
    {
        if (strlen($texte) > $taille)
        {
            $texte = substr($texte, 0, $taille);
            $taille -= ($taille * 0.1);

            $texte = preg_replace('!([\s.,;:\!?])[^\s.,;:\!?]*?$!', '\\1', $texte);
            $texte.= $etc;
        }

        return $texte;
    }

    static public function replace($texte, $expression, $replace, $modif='UsimsS')
    {
        return preg_replace('/'.$expression.'/'.$modif, $replace, $texte);
    }

    static public function plus($a, $b)
    {
        return $a + $b;
    }

    static public function moins($a, $b)
    {
        return $a - $b;
    }

    static public function mult($a, $b)
    {
        return $a * $b;
    }

    static public function div($a, $b)
    {
        return $b ? $a / $b : 0;
    }

    static public function modulo($a, $mod, $add)
    {
        return ($mod ? $nb % $mod : 0) + $add;
    }

    static public function vide($value)
    {
        return '';
    }

    static public function concat()
    {
        return implode('', func_get_args());
    }

    static public function singulier_ou_pluriel($nb, $singulier, $pluriel, $var = null)
    {
        if (!$nb)
            return '';

        if ($nb == 1)
            return str_replace('@'.$var.'@', $nb, $singulier);
        else
            return str_replace('@'.$var.'@', $nb, $pluriel);
    }

    static public function date_w3c($date)
    {
        return date(DATE_W3C, $date);
    }

    static public function et($value, $test)
    {
        return ($value && $test);
    }

    static public function ou($value, $test)
    {
        return ($value || $test);
    }

    static public function xou($value, $test)
    {
        return ($value XOR $test);
    }

    static public function oui($value)
    {
        return $value ? true : false;
    }

    static public function non($value)
    {
        return !$value ? true : false;
    }

    static public function superieur_a($value, $test)
    {
        return ($value > $test) ? true : false;
    }

    static public function superieur_ou_egal_a($value, $test)
    {
        return ($value >= $test) ? true : false;
    }

    static public function inferieur_a($value, $test)
    {
        return ($value < $test) ? true : false;
    }

    static public function inferieur_ou_egal_a($value, $test)
    {
        return ($value <= $test) ? true : false;
    }

    static public function euros($value)
    {
        return str_replace(' ', '&nbsp;', number_format($value, (round($value) == round($value, 2) ? 0 : 2), ',', ' ')) . '&nbsp;€';
    }

    static public function taille_en_octets($value)
    {
        return Utils::format_bytes($value);
    }
}
