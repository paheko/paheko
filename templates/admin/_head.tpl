<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{$title|escape}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="{$www_url}style/admin.css" media="screen,projection,handheld" />
</head>

<body>

{if empty($is_popup)}
<div class="header">
    <h1>{$title|escape}</h1>

    {if $is_logged}
    <ul class="menu">
        <li class="home{if $current == 'home'} current{/if}"><a href="{$www_url}admin/">Accueil</a></li>
        {if $user.droits.membres >= Garradin_Membres::DROIT_ACCES}
            <li class="list_members{if $current == 'membres'} current{/if}"><a href="{$www_url}admin/membres/">Membres</a>
            {if $user.droits.membres >= Garradin_Membres::DROIT_ECRITURE}
            <ul>
                <li class="add_member{if $current == 'membres/ajouter'} current{/if}"><a href="{$www_url}admin/membres/ajouter.php">Ajouter</a></li>
                {if $user.droits.membres >= Garradin_Membres::DROIT_ADMIN}
                <li class="member_cats{if $current == 'membres/categories'} current{/if}"><a href="{$www_url}admin/membres/categories.php">Catégories</a></li>
                <li class="members_mail{if $current == 'membres/message_collectif'} current{/if}"><a href="{$www_url}admin/membres/message_collectif.php">Message collectif</a></li>
                {/if}
            </ul>
            {/if}
            </li>
        {/if}
        {*
        {if $user.droits.compta >= Garradin_Membres::DROIT_ACCES}
            <li class="compta{if $current == 'compta'} current{/if}"><a href="{$www_url}compta/">Comptabilité</a>
            {if $user.droits.compta >= Garradin_Membres::DROIT_ADMIN}
            <ul>
                <li class="compta_gestion{if $current == 'compta/gestion'} current{/if}"><a href="{$www_url}admin/compta/operations.php">Opérations</a></li>
                <li class="compta_cats{if $current == 'compta/categories'} current{/if}"><a href="{$www_url}admin/compta/categories.php">Catégories</a></li>
                <li class="compta_comptes{if $current == 'compta/comptes'} current{/if}"><a href="{$www_url}admin/compta/comptes.php">Comptes</a></li>
            </ul>
            {/if}
            </li>
        *}
        {if $user.droits.wiki >= Garradin_Membres::DROIT_ACCES}
            <li class="wiki{if $current == 'wiki'} current{/if}"><a href="{$www_url}admin/wiki/">Wiki</a>
            <ul>
                <li class="wiki_recent{if $current == 'wiki/recent'} current{/if}"><a href="{$www_url}admin/wiki/recent.php">Dernières modifications</a>
                {*<li class="wiki_suivi{if $current == 'wiki/suivi'} current{/if}"><a href="{$www_url}admin/wiki/suivi.php">Mes pages suivies</a>*}
                {*<li class="wiki_contribution{if $current == 'wiki/contribution'} current{/if}"><a href="{$www_url}admin/wiki/contributions.php">Mes contributions</a>*}
            </ul>
            </li>
        {/if}
        {if $user.droits.config >= Garradin_Membres::DROIT_ADMIN}
            <li class="config{if $current == 'config'} current{/if}"><a href="{$www_url}admin/config/">Configuration</a>
        {/if}
        {if count($config.champs_modifiables_membre) > 0}
            <li class="mes_infos{if $current == 'mes_infos'} current{/if}"><a href="{$www_url}admin/mes_infos.php">Mes infos</a>
        {/if}
        <li class="logout"><a href="{$www_url}admin/logout.php">Déconnexion</a></li>
    </ul>
    {/if}
</div>
{/if}

<div class="page">