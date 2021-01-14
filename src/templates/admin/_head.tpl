<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <meta charset="utf-8" />
    <title>{$title}</title>
    <link rel="icon" type="image/png" href="{$admin_url}static/icon.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, target-densitydpi=device-dpi" />
    <link rel="stylesheet" type="text/css" href="{$admin_url}static/admin.css?{$version_hash}" media="all" />
    <script type="text/javascript" src="{$admin_url}static/scripts/global.js?{$version_hash}"></script>
    {if isset($custom_js)}
        {foreach from=$custom_js item="js"}
            <script type="text/javascript" src="{$admin_url}static/scripts/{$js}?{$version_hash}"></script>
        {/foreach}
    {/if}
    {if isset($custom_css)}
        {foreach from=$custom_css item="css"}
            <link rel="stylesheet" type="text/css" href="{$admin_url}static/{$css}?{$version_hash}" media="all" />
        {/foreach}
    {/if}
    {if isset($plugin_css)}
        {foreach from=$plugin_css item="css"}
            <link rel="stylesheet" type="text/css" href="{plugin_url file=$css}?{$version_hash}" />
        {/foreach}
    {/if}
    {if isset($plugin_js)}
        {foreach from=$plugin_js item="js"}
            <script type="text/javascript" src="{plugin_url file=$js}?{$version_hash}"></script>
        {/foreach}
    {/if}
    <link rel="stylesheet" type="text/css" href="{$admin_url}static/print.css?{$version_hash}" media="print" />
    <link rel="stylesheet" type="text/css" href="{$admin_url}static/handheld.css?{$version_hash}" media="handheld,screen and (max-width:981px)" />
    {if isset($config)}
        {custom_colors config=$config}
    {/if}
</head>

<body{if !empty($body_id)} id="{$body_id}"{/if}>

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
                {foreach from=$plugins_menu key="plugin_id" item="name"}
                    <li class="plugins {if $current == sprintf("plugin_%s", $plugin_id)} current{/if}"><a href="{plugin_url id=$plugin_id}">{$name}</a></li>
                {/foreach}
                </ul>
            {/if}
        </li>
        {if $session->canAccess('membres', Membres::DROIT_ACCES)}
            <li class="member list{if $current == 'membres'} current{elseif $current_parent == 'membres'} current_parent{/if}"><a href="{$admin_url}membres/"><b class="icn">👪</b><i> Membres</i></a>
            {if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
            <ul>
                <li class="member new{if $current == 'membres/ajouter'} current{/if}"><a href="{$admin_url}membres/ajouter.php">Ajouter</a></li>
                <li class="{if $current == 'membres/services'} current{/if}"><a href="{$admin_url}services/">Activités &amp; cotisations</a></li>
                <li class="member message{if $current == 'membres/message'} current{/if}"><a href="{$admin_url}membres/message_collectif.php">Message collectif</a></li>
            </ul>
            {/if}
            </li>
        {/if}
        {if $session->canAccess('compta', Membres::DROIT_ACCES)}
            <li class="{if $current == 'acc'} current{elseif $current_parent == 'acc'} current_parent{/if}"><a href="{$admin_url}acc/"><b>€</b><i> Comptabilité</i></a>
            <ul>
            {if $session->canAccess('compta', Membres::DROIT_ECRITURE)}
                <li class="{if $current == 'acc/new'} current{/if}"><a href="{$admin_url}acc/transactions/new.php">Saisie</a></li>
            {/if}
                <li class="{if $current == 'acc/accounts'} current{/if}"><a href="{$admin_url}acc/accounts/">Comptes</a></li>
                <li class="{if $current == 'acc/simple'} current{/if}"><a href="{$admin_url}acc/accounts/simple.php">Suivi des écritures</a></li>
                <li class="{if $current == 'acc/years'} current{/if}"><a href="{$admin_url}acc/years/">Exercices &amp; rapports</a></li>
            {if $session->canAccess('compta', Membres::DROIT_ECRITURE)}
                <li class="{if $current == 'acc/charts'} current{/if}"><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
            {/if}
            </ul>
            </li>
        {/if}
        {if $session->canAccess('documents', Membres::DROIT_ACCES)}
            <li class="{if $current == 'docs'} current{elseif $current_parent == 'docs'} current_parent{/if}"><a href="{$admin_url}docs/"><b class="icn">🗀</b><i> Fichiers</i></a>
            <ul>
                <li class="{if $current == 'docs/recent'} current{/if}"><a href="{$admin_url}docs/recent.php">Récents</a></li>
            </ul>
            </li>
        {/if}
        {if $session->canAccess('web', Membres::DROIT_ACCES)}
            <li class="{if $current == 'web'} current{elseif $current_parent == 'web'} current_parent{/if}"><a href="{$admin_url}web/"><b class="icn">🖻</b><i> Site web</i></a>
            {* TODO
            <ul>
                <li class="{if $current == 'web/themes'} current{/if}"><a href="{$admin_url}web/themes/">Thèmes</a></li>
            </ul>
            *}
            </li>
        {/if}
        {if $session->canAccess('config', Membres::DROIT_ADMIN)}
            <li class="main config{if $current == 'config'} current{elseif $current_parent == 'config'} current_parent{/if}"><a href="{$admin_url}config/"><b class="icn">☸</b><i> Configuration</i></a>
        {/if}
        <li class="{if $current == 'mes_infos'} current{elseif $current_parent == 'mes_infos'} current_parent{/if}">
            <a href="{$admin_url}mes_infos.php"><b class="icn">👤</b><i> Mes infos personnelles</i></a>
            <ul>
                <li{if $current == 'my_services'}  class="current"{/if}><a href="{$admin_url}my_services.php">Mes activités &amp; cotisations</a></li>
            </ul>
        </li>
        {if !defined('Garradin\LOCAL_LOGIN') || !LOCAL_LOGIN}
        <li class="logout"><a href="{$admin_url}logout.php"><b class="icn">⤝</b><i> Déconnexion</i></a></li>
        {/if}
    {/if}
    </ul>
    </nav>

    <h1>{$title}</h1>
</header>
{/if}

<main>