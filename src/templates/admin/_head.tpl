<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <meta charset="utf-8" />
    <title>{$title}</title>
    <link rel="icon" type="image/png" href="{$admin_url}static/icon.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, target-densitydpi=device-dpi" />
    <link rel="stylesheet" type="text/css" href="{$admin_url}static/admin.css?a" media="all" />
    {if isset($js) || isset($custom_js)}
        <script type="text/javascript" src="{$admin_url}static/scripts/global.js"></script>
    {/if}
    {if isset($custom_js)}
        {foreach from=$custom_js item="js"}
            <script type="text/javascript" src="{$admin_url}static/scripts/{$js}"></script>
        {/foreach}
    {/if}
    {if isset($custom_css)}
        {foreach from=$custom_css item="css"}
            <link rel="stylesheet" type="text/css" href="{$admin_url}static/{$css}" media="all" />
        {/foreach}
    {/if}
    {if isset($plugin_css)}
        {foreach from=$plugin_css item="css"}
            <link rel="stylesheet" type="text/css" href="{plugin_url file=$css}" />
        {/foreach}
    {/if}
    {if isset($plugin_js)}
        {foreach from=$plugin_js item="js"}
            <script type="text/javascript" src="{plugin_url file=$js}"></script>
        {/foreach}
    {/if}
    <link rel="stylesheet" type="text/css" href="{$admin_url}static/print.css?a" media="print" />
    <link rel="stylesheet" type="text/css" href="{$admin_url}static/handheld.css?a" media="handheld,screen and (max-width:981px)" />
    {if isset($config)}
        {custom_colors config=$config}
    {/if}
</head>

<body{if !empty($body_id)} id="{$body_id}"{/if} data-url="{$admin_url}">

{if empty($is_popup)}
<header class="header">
    <nav class="menu">
    <ul>
    {if !$is_logged}
        <li><a href="{$www_url}">&larr; Retour au site</a></li>
        <li><a href="{$admin_url}">Connexion</a>
            <ul>
                <li><a href="{$admin_url}password.php">Mot de passe perdu</a>
            </ul>
        </li>
    {else}
    <?php
    $current_parent = substr($current, 0, strpos($current, '/'));
    ?>
        <li class="home{if $current == 'home'} current{elseif $current_parent == 'home'} current_parent{/if}">
            <a href="{$admin_url}"><b class="icn">⌂</b><i> Accueil</i></a>
            {if !empty($plugins_menu)}
                <ul>
                {foreach from=$plugins_menu key="id" item="name"}
                    <li class="plugins {if $current == sprintf("plugin_%s", $id)} current{/if}"><a href="{plugin_url id=$id}">{$name}</a></li>
                {/foreach}
                </ul>
            {/if}
        </li>
        {if $session->canAccess('membres', Garradin\Membres::DROIT_ACCES)}
            <li class="member list{if $current == 'membres'} current{elseif $current_parent == 'membres'} current_parent{/if}"><a href="{$admin_url}membres/"><b class="icn">👪</b><i> Membres</i></a>
            {if $session->canAccess('membres', Garradin\Membres::DROIT_ECRITURE)}
            <ul>
                <li class="member new{if $current == 'membres/ajouter'} current{/if}"><a href="{$admin_url}membres/ajouter.php">Ajouter</a></li>
                <li class="member cotisations{if $current == 'membres/cotisations'} current{/if}"><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
                {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN)}
                <li class="member admin config{if $current == 'membres/categories'} current{/if}"><a href="{$admin_url}membres/categories/">Catégories</a></li>
                <li class="members admin mail{if $current == 'membres/message_collectif'} current{/if}"><a href="{$admin_url}membres/message_collectif.php">Message collectif</a></li>
                {/if}
            </ul>
            {/if}
            </li>
        {/if}
        {if $session->canAccess('compta', Garradin\Membres::DROIT_ACCES)}
            <li class="compta{if $current == 'compta'} current{elseif $current_parent == 'compta'} current_parent{/if}"><a href="{$admin_url}compta/"><b>€</b><i> Comptabilité</i></a>
            <ul>
            {if $session->canAccess('compta', Garradin\Membres::DROIT_ECRITURE)}
                <li class="compta new{if $current == 'compta/saisie'} current{/if}"><a href="{$admin_url}compta/operations/saisir.php">Saisie</a></li>
            {/if}
                <li class="compta list{if $current == 'compta/gestion'} current{/if}"><a href="{$admin_url}compta/operations/">Suivi des opérations</a></li>
                <li class="compta banks{if $current == 'compta/banques'} current{/if}"><a href="{$admin_url}compta/banques/">Banques &amp; caisse</a></li>
            {if $session->canAccess('compta', Garradin\Membres::DROIT_ADMIN)}
                <li class="compta admin config{if $current == 'compta/categories'} current{/if}"><a href="{$admin_url}compta/categories/">Catégories &amp; comptes</a></li>
            {/if}
                <li class="compta admin reports{if $current == 'compta/exercices'} current{/if}"><a href="{$admin_url}compta/exercices/">Exercices &amp; projets</a></li>
            </ul>
            </li>
        {/if}
        {if $session->canAccess('wiki', Garradin\Membres::DROIT_ACCES)}
            <li class="wiki{if $current == 'wiki'} current{elseif $current_parent == 'wiki'} current_parent{/if}"><a href="{$admin_url}wiki/"><b class="icn">✎</b><i> Wiki</i></a>
            <ul>
                <li class="wiki list{if $current == 'wiki/recent'} current{/if}"><a href="{$admin_url}wiki/recent.php">Dernières modifications</a>
                <li class="wiki search{if $current == 'wiki/chercher'} current{/if}"><a href="{$admin_url}wiki/chercher.php">Recherche</a>
                {*<li class="wiki follow{if $current == 'wiki/suivi'} current{/if}"><a href="{$admin_url}wiki/suivi.php">Mes pages suivies</a>*}
                {*<li class="wiki follow{if $current == 'wiki/contribution'} current{/if}"><a href="{$admin_url}wiki/contributions.php">Mes contributions</a>*}
            </ul>
            </li>
        {/if}
        {if $session->canAccess('config', Garradin\Membres::DROIT_ADMIN)}
            <li class="main config{if $current == 'config'} current{elseif $current_parent == 'config'} current_parent{/if}"><a href="{$admin_url}config/"><b class="icn">☸</b><i> Configuration</i></a>
        {/if}
        <li class="my config{if $current == 'mes_infos'} current{elseif $current_parent == 'mes_infos'} current_parent{/if}"><a href="{$admin_url}mes_infos.php"><b class="icn">👤</b><i> Mes infos personnelles</i></a>
            <ul>
                <li class="my cotisations{if $current == 'mes_cotisations'} current{/if}"><a href="{$admin_url}mes_cotisations.php">Mes cotisations</a></li>
            </ul>
        </li>
        {if !defined('Garradin\LOCAL_LOGIN') || !Garradin\LOCAL_LOGIN}
        <li class="logout"><a href="{$admin_url}logout.php"><b class="icn">⤝</b><i> Déconnexion</i></a></li>
        {/if}
    {/if}
    </ul>
    </nav>

    <h1>{$title}</h1>
</header>
{/if}

<main>