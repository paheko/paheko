<?php

require_once GARRADIN_ROOT . '/include/libs/template_lite/class.template.php';

class Garradin_TPL extends Template_Lite
{
    static protected $_instance = null;

    static public function getInstance()
    {
        return self::$_instance ?: self::$_instance = new Garradin_TPL;
    }

    private function __clone()
    {
    }

    public function __construct()
    {
        parent::__construct();

        $this->cache = false;

        $this->compile_dir = GARRADIN_ROOT . '/cache/compiled';
        $this->template_dir = GARRADIN_ROOT . '/templates';

        $this->compile_check = true;

        $this->reserved_template_varname = 'tpl';

        $this->assign('www_url', WWW_URL);
        $this->assign('self_url', utils::getSelfUrl());

        $this->assign('is_logged', false);
    }
}

$tpl = Garradin_TPL::getInstance();

if (class_exists('Garradin_Config'))
{
    $tpl->assign('config', Garradin_Config::getInstance()->getConfig());
}
else
{
    $tpl->assign('config', false);
}

function tpl_csrf_field($params)
{
    $name = utils::CSRF_field_name($params['key']);
    $value = utils::CSRF_create($params['key']);

    return '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
}

function tpl_form_field($params)
{
    $name = $params['name'];

    if (isset($_POST[$name]))
        $value = $_POST[$name];
    elseif (isset($params['data']) && isset($params['data'][$name]))
        $value = $params['data'][$name];
    elseif (isset($params['default']))
        $value = $params['default'];
    else
        $value = '';

    if (isset($params['checked']))
    {
        if ($value == $params['checked'])
            return ' checked="checked" ';

        return '';
    }

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function tpl_format_tel($n)
{
    $n = preg_replace('![^\d\+]!', '', $n);
    $n = preg_replace('!(\+?\d{2})!', '\\1 ', $n);
    return $n;
}

function tpl_strftime_fr($ts, $format)
{
    return utils::strftime_fr($format, $ts);
}

function tpl_date_fr($ts, $format)
{
    return utils::date_fr($format, $ts);
}

function tpl_format_droits($params)
{
    $droits = $params['droits'];

    $out = array('connexion' => '', 'inscription' => '', 'membres' => '', 'compta' => '',
        'wiki' => '', 'config' => '');
    $classes = array(
        Garradin_Membres::DROIT_AUCUN   =>  'aucun',
        Garradin_Membres::DROIT_ACCES   =>  'acces',
        Garradin_Membres::DROIT_ECRITURE=>  'ecriture',
        Garradin_Membres::DROIT_ADMIN   =>  'admin',
    );

    foreach ($droits as $cle=>$droit)
    {
        $cle = str_replace('droit_', '', $cle);

        if (array_key_exists($cle, $out))
        {

            $class = $classes[$droit];
            $desc = false;
            $s = false;

            if ($cle == 'connexion')
            {
                if ($droit == Garradin_Membres::DROIT_AUCUN)
                    $desc = 'N\'a pas le droit de se connecter';
                else
                    $desc = 'A le droit de se connecter';
            }
            elseif ($cle == 'inscription')
            {
                if ($droit == Garradin_Membres::DROIT_AUCUN)
                    $desc = 'N\'a pas le droit de s\'inscrire seul';
                else
                    $desc = 'A le droit de s\'inscrire seul';
            }
            elseif ($cle == 'config')
            {
                $s = '&#x2611;';

                if ($droit == Garradin_Membres::DROIT_AUCUN)
                    $desc = 'Ne peut modifier la configuration';
                else
                    $desc = 'Peut modifier la configuration';
            }
            elseif ($cle == 'compta')
            {
                $s = '&euro;';
            }

            if (!$s)
                $s = strtoupper($cle[0]);

            if (!$desc)
            {
                $desc = ucfirst($cle). ' : ';

                if ($droit == Garradin_Membres::DROIT_AUCUN)
                    $desc .= 'Pas accès';
                elseif ($droit == Garradin_Membres::DROIT_ACCES)
                    $desc .= 'Lecture uniquement';
                elseif ($droit == Garradin_Membres::DROIT_ECRITURE)
                    $desc .= 'Lecture & écriture';
                else
                    $desc .= 'Administration';
            }

            $out[$cle] = '<b class="'.$class.' '.$cle.'" title="'
                .htmlspecialchars($desc, ENT_QUOTES, 'UTF-8').'">'.$s.'</b>';
        }
    }

    return implode(' ', $out);
}

function tpl_format_wiki($str)
{
    $str = utils::htmlLinksOnUrls($str);
    $str = utils::htmlSpip($str);
    $str = utils::htmlGarbage2xhtml($str);
    return $str;
}

function tpl_liens_wiki($str, $prefix)
{
    return preg_replace('!<a href="([^/.]+)">!ie', '"<a href=\"".$prefix.Garradin_Wiki::transformTitleToURI("$1")."\">"', $str);
}

function tpl_pagination($params)
{
    if (!isset($params['url']) || !isset($params['page']) || !isset($params['bypage']) || !isset($params['total']))
        throw new BadFunctionCallException("Paramètre manquant pour pagination");

    if ($params['total'] == -1)
        return '';

    $pagination = utils::getGenericPagination($params['page'], $params['total'], $params['bypage']);

    if (empty($pagination))
        return '';

    $out = '<ul class="pagination">';

    foreach ($pagination as &$page)
    {
        $attributes = '';

        if (!empty($page['class']))
            $attributes .= ' class="' . htmlspecialchars($page['class']) . '" ';

        $out .= '<li'.$attributes.'>';

        $attributes = '';

        if (!empty($page['accesskey']))
            $attributes .= ' accesskey="' . htmlspecialchars($page['accesskey']) . '" ';

        $out .= '<a' . $attributes . ' href="' . str_replace('[ID]', htmlspecialchars($page['id']), $params['url']) . '">';
        $out .= htmlspecialchars($page['label']);
        $out .= '</a>';
        $out .= '</li>' . "\n";
    }

    $out .= '</ul>';

    return $out;
}

function tpl_diff($params)
{
    if (!isset($params['old']) || !isset($params['new']))
    {
        throw new Template_Exception('Paramètres old et new requis.');
    }

    $old = $params['old'];
    $new = $params['new'];

    require_once GARRADIN_ROOT . '/include/libs/diff/class.simplediff.php';
    $diff = simpleDiff::diff_to_array(false, $old, $new, 3);

    $out = '<table class="diff">';
    $prev = key($diff);

    foreach ($diff as $i=>$line)
    {
        if ($i > $prev + 1)
        {
            $out .= '<tr><td colspan="5" class="separator"><hr /></td></tr>';
        }

        list($type, $old, $new) = $line;

        $class1 = $class2 = '';
        $t1 = $t2 = '';

        if ($type == simpleDiff::INS)
        {
            $class2 = 'ins';
            $t2 = '<b>+</b>';
            $old = htmlspecialchars($old, ENT_QUOTES, 'UTF-8');
            $new = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
        }
        elseif ($type == simpleDiff::DEL)
        {
            $class1 = 'del';
            $t1 = '<b>-</b>';
            $old = htmlspecialchars($old, ENT_QUOTES, 'UTF-8');
            $new = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
        }
        elseif ($type == simpleDiff::CHANGED)
        {
            $class1 = 'del';
            $class2 = 'ins';
            $t1 = '<b>-</b>';
            $t2 = '<b>+</b>';

            $lineDiff = simpleDiff::wdiff($old, $new);
            $lineDiff = htmlspecialchars($lineDiff, ENT_QUOTES, 'UTF-8');

            // Don't show new things in deleted line
            $old = preg_replace('!\{\+(?:.*)\+\}!U', '', $lineDiff);
            $old = str_replace('  ', ' ', $old);
            $old = str_replace('-] [-', ' ', $old);
            $old = preg_replace('!\[-(.*)-\]!U', '<del>\\1</del>', $old);

            // Don't show old things in added line
            $new = preg_replace('!\[-(?:.*)-\]!U', '', $lineDiff);
            $new = str_replace('  ', ' ', $new);
            $new = str_replace('+} {+', ' ', $new);
            $new = preg_replace('!\{\+(.*)\+\}!U', '<ins>\\1</ins>', $new);
        }
        else
        {
            $old = htmlspecialchars($old, ENT_QUOTES, 'UTF-8');
            $new = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
        }

        $out .= '<tr>';
        $out .= '<td class="line">'.($i+1).'</td>';
        $out .= '<td class="leftChange">'.$t1.'</td>';
        $out .= '<td class="leftText '.$class1.'">'.$old.'</td>';
        $out .= '<td class="rightChange">'.$t2.'</td>';
        $out .= '<td class="rightText '.$class2.'">'.$new.'</td>';
        $out .= '</tr>';

        $prev = $i;
    }

    $out .= '</table>';
    return $out;
}

function tpl_select_compte($params)
{
    $name = $params['name'];
    $comptes = $params['comptes'];
    $selected = isset($params['data'][$params['name']]) ? $params['data'][$params['name']] : utils::post($name);

    $out = '<select name="'.$name.'" id="f_'.$name.'" class="large">';

    foreach ($comptes as $compte)
    {
        if (!isset($compte['id'][1]))
        {
            $out.= '<optgroup label="'.htmlspecialchars($compte['libelle'], ENT_QUOTES, 'UTF-8', false).'" class="niveau_1"></optgroup>';
        }
        elseif (!isset($compte['id'][2]) && empty($params['create']))
        {
            if ($compte['id'] > 10)
                $out.= '</optgroup>';

            $out.= '<optgroup label="'.htmlspecialchars($compte['id'] . ' - ' . $compte['libelle'], ENT_QUOTES, 'UTF-8', false).'" class="niveau_2">';
        }
        else
        {
            $out .= '<option value="'.htmlspecialchars($compte['id'], ENT_QUOTES, 'UTF-8', false).'" class="niveau_'.strlen($compte['id']).'"';

            if ($selected == $compte['id'])
            {
                $out .= ' selected="selected"';
            }

            $out .= '>' . htmlspecialchars($compte['id'] . ' - ' . $compte['libelle'], ENT_QUOTES, 'UTF-8', false);
            $out .= '</option>';
        }
    }

    $out .= '</optgroup>';
    $out .= '</select>';

    return $out;
}

function escape_money($number)
{
    return number_format((float)$number, 2, ',', ' ');
}

$tpl->register_function('csrf_field', 'tpl_csrf_field');
$tpl->register_function('form_field', 'tpl_form_field');
$tpl->register_function('select_compte', 'tpl_select_compte');

$tpl->register_function('format_droits', 'tpl_format_droits');

$tpl->register_function('pagination', 'tpl_pagination');

$tpl->register_function('diff', 'tpl_diff');

$tpl->register_modifier('get_country_name', array('utils', 'getCountryName'));
$tpl->register_modifier('format_tel', 'tpl_format_tel');
$tpl->register_modifier('format_wiki', 'tpl_format_wiki');
$tpl->register_modifier('liens_wiki', 'tpl_liens_wiki');
$tpl->register_modifier('escape_money', 'escape_money');
$tpl->register_modifier('abs', 'abs');

//$tpl->register_modifier('retard_cotisation', array('Garradin_Membres', 'checkCotisation'));

$tpl->register_modifier('strftime_fr', 'tpl_strftime_fr');
$tpl->register_modifier('date_fr', 'tpl_date_fr');

?>