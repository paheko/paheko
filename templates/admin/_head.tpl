<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{$title|escape}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="{$www_url}style/admin.css" media="screen,projection,handheld" />
</head>

<body>

<div class="header">
    <h1>{$title|escape}</h1>

    {if $is_logged}
    <ul class="menu">
        <li class="home{if $self_page == ''} current{/if}"><a href="{$www_url}admin/">Accueil</a></li>
        {if $user.droits.membres >= Garradin_Membres::DROIT_ACCES}
            <li class="list_members{if $self_page == 'membres/'} current{/if}"><a href="{$www_url}admin/membres/">Membres</a>
            {if $user.droits.membres >= Garradin_Membres::DROIT_ADMIN}
            <ul>
                <li class="add_member{if $self_page == 'membres/ajouter.php'} current{/if}"><a href="{$www_url}admin/membres/ajouter.php">Ajouter</a></li>
                <li class="member_cats{if $self_page == 'membres/categories.php'} current{/if}"><a href="{$www_url}admin/membres/categories.php">Catégories</a></li>
            </ul>
            {/if}
            </li>
        {/if}
        {if $user.droits.compta >= Garradin_Membres::DROIT_ACCES}
            <li class="compta{if $self_page == 'compta/'} current{/if}"><a href="{$www_url}compta/">Comptabilité</a>
            {if $user.droits.compta >= Garradin_Membres::DROIT_ADMIN}
            <ul>
                <li class="compta_gestion{if $self_page == 'compta/gestion/'} current{/if}"><a href="{$www_url}admin/compta/operations.php">Opérations</a></li>
                <li class="compta_cats{if $self_page == 'compta/categories.php'} current{/if}"><a href="{$www_url}admin/compta/categories.php">Catégories</a></li>
                <li class="compta_comptes{if $self_page == 'compta/comptes.php'} current{/if}"><a href="{$www_url}admin/compta/comptes.php">Comptes</a></li>
            </ul>
            {/if}
            </li>
        {/if}
        {if $user.droits.wiki >= Garradin_Membres::DROIT_ACCES}
            <li class="wiki{if $self_page == 'wiki/'} current{/if}"><a href="{$www_url}wiki/">Wiki</a>
            <ul>
                <li class="wiki_my{if $self_page == 'wiki/suivi/'} current{/if}"><a href="{$www_url}wiki/suivi/">Mes pages suivies</a>
            </ul>
            </li>
        {/if}
        <li class="wiki{if $self_page == 'wiki/'} current{/if}"><a href="{$www_url}wiki/">Wiki</a>
        <li class="logout"><a href="{$www_url}admin/logout.php">Déconnexion</a></li>
    </ul>
    {/if}
</div>

<div class="page">