<?php

namespace Garradin;

class Squelette_Filtres
{
    static private $alt = [];

    static public $desactiver_defaut = [
        'formatter_texte',
        'entites_html',
        'proteger_contact',
        'echapper_xml',
    ];

    static public function date($date, $format)
    {
        return strftime($format, $date);
    }

    static public function date_en_francais($date)
    {
        return ucfirst(strtolower(Utils::strftime_fr($date, '%A %e %B %Y')));
    }

    static public function heure_en_francais($date)
    {
        return Utils::strftime_fr($date, '%Hh%M');
    }

    static public function mois_en_francais($date)
    {
        return Utils::strftime_fr($date, '%B %Y');
    }

    static public function date_perso($date, $format)
    {
        return Utils::strftime_fr($date, $format);
    }

    static public function date_intelligente($date, $avec_heure = true)
    {
        return Utils::relative_date($date, $avec_heure);
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

    static public function formatter_texte($texte)
    {
        $texte = Utils::SkrivToHTML($texte);
        $texte = self::typo_fr($texte);

        // Liens wiki
        $texte = preg_replace_callback('!<a href="([^/.:@]+)">!i', function ($matches) {
            return '<a href="' . WWW_URL . Wiki::transformTitleToURI($matches[1]) . '">';
        }, $texte);

        return $texte;
    }

    static public function typo_fr($str, $html = true)
    {
        $space = $html ? '&nbsp;' : ' ';
        $str = preg_replace('/(?:[\h]|&nbsp;)*([?!:»])(\s+|$)/u', $space.'\\1\\2', $str);
        $str = preg_replace('/(^|\s+)([«])(?:[\h]|&nbsp;)*/u', '\\1\\2'.$space, $str);
        return $str;
    }
}
