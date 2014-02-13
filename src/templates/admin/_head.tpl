<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{$title|escape}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, target-densitydpi=device-dpi" />
    <link rel="stylesheet" type="text/css" href="{$admin_url}static/admin.css" media="all" />
    <link rel="stylesheet" type="text/css" href="{$admin_url}static/print.css" media="print" />
    <link rel="stylesheet" type="text/css" href="{$admin_url}static/handheld.css" media="handheld,screen and (max-width:981px)" />
    {if isset($js)}
        <script type="text/javascript" src="{$admin_url}static/global.js"></script>
    {/if}
    {if isset($custom_js)}
        {foreach from=$custom_js item="js"}
            <script type="text/javascript" src="{$admin_url}static/{$js|escape}"></script>
        {/foreach}
    {/if}
</head>

<body{if !empty($body_id)} id="{$body_id|escape}"{/if} data-url="{$admin_url|escape}">

{if empty($is_popup)}
<div class="header">
    <ul class="menu">
    {if !$is_logged}
        <li><a href="{$www_url}">&larr; Retour au site</a></li>
        <li><a href="{$admin_url}">Connexion</a>
            <ul>
                <li><a href="{$admin_url}password.php">Mot de passe perdu</a>
            </ul>
        </li>
    {else}
        <li class="home{if $current == 'home'} current{/if}"><a href="{$admin_url}">Accueil</a></li>
        {if $user.droits.membres >= Garradin\Membres::DROIT_ACCES}
            <li class="member list{if $current == 'membres'} current{/if}"><a href="{$admin_url}membres/">Membres <small>({$nb_membres|escape})</small></a>
            {if $user.droits.membres >= Garradin\Membres::DROIT_ECRITURE}
            <ul>
                <li class="member new{if $current == 'membres/ajouter'} current{/if}"><a href="{$admin_url}membres/ajouter.php">Ajouter</a></li>
                <li class="member cotisations{if $current == 'membres/transactions'} current{/if}"><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
                {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
                <li class="member admin config{if $current == 'membres/categories'} current{/if}"><a href="{$admin_url}membres/categories.php">Catégories</a></li>
                <li class="members admin mail{if $current == 'membres/message_collectif'} current{/if}"><a href="{$admin_url}membres/message_collectif.php">Message collectif</a></li>
                {/if}
            </ul>
            {/if}
            </li>
        {/if}
        {if $user.droits.compta >= Garradin\Membres::DROIT_ACCES}
            <li class="compta{if $current == 'compta'} current{/if}"><a href="{$admin_url}compta/">Comptabilité</a>
            <ul>
            {if $user.droits.compta >= Garradin\Membres::DROIT_ECRITURE}
                <li class="compta new{if $current == 'compta/saisie'} current{/if}"><a href="{$admin_url}compta/operations/saisir.php">Saisie</a></li>
            {/if}
                <li class="compta list{if $current == 'compta/gestion'} current{/if}"><a href="{$admin_url}compta/operations/">Suivi des opérations</a></li>
                <li class="compta banks{if $current == 'compta/banques'} current{/if}"><a href="{$admin_url}compta/banques/">Banques &amp; caisse</a></li>
            {if $user.droits.compta >= Garradin\Membres::DROIT_ADMIN}
                <li class="compta admin config{if $current == 'compta/categories'} current{/if}"><a href="{$admin_url}compta/categories/">Catégories &amp; comptes</a></li>
            {/if}
                <li class="compta admin reports{if $current == 'compta/exercices'} current{/if}"><a href="{$admin_url}compta/exercices/">Exercices</a></li>
            </ul>
            </li>
        {/if}
        {if $user.droits.wiki >= Garradin\Membres::DROIT_ACCES}
            <li class="wiki{if $current == 'wiki'} current{/if}"><a href="{$admin_url}wiki/">Wiki</a>
            <ul>
                <li class="wiki list{if $current == 'wiki/recent'} current{/if}"><a href="{$admin_url}wiki/recent.php">Dernières modifications</a>
                <li class="wiki search{if $current == 'wiki/chercher'} current{/if}"><a href="{$admin_url}wiki/chercher.php">Recherche</a>
                {if $user.droits.wiki >= Garradin\Membres::DROIT_ECRITURE}
                {/if}
                {*<li class="wiki follow{if $current == 'wiki/suivi'} current{/if}"><a href="{$admin_url}wiki/suivi.php">Mes pages suivies</a>*}
                {*<li class="wiki follow{if $current == 'wiki/contribution'} current{/if}"><a href="{$admin_url}wiki/contributions.php">Mes contributions</a>*}
            </ul>
            </li>
        {/if}
        {if $user.droits.config >= Garradin\Membres::DROIT_ADMIN}
            <li class="main config{if $current == 'config'} current{/if}"><a href="{$admin_url}config/">Configuration</a>
        {/if}
        <li class="my config{if $current == 'mes_infos'} current{/if}"><a href="{$admin_url}mes_infos.php">Mes infos personnelles</a>
            <ul>
                <li class="my cotisations{if $current == 'mes_cotisations'} current{/if}"><a href="{$admin_url}mes_cotisations.php">Mes cotisations</a></li>
            </ul>
        </li>
        {if !defined('Garradin\LOCAL_LOGIN')}
        <li class="logout"><a href="{$admin_url}logout.php">Déconnexion</a></li>
        {/if}
    {/if}
    </ul>

    <h1>{$title|escape}</h1>
</div>
{/if}

<div class="page">