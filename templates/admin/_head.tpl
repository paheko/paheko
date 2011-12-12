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
        {if has_right('MEMBRE_GESTION', $user.rights)}
            <li class="add_member{if $self_page == 'membres/ajouter.php'} current{/if}"><a href="{$www_url}admin/membres/ajouter.php">Ajouter un membre</a></li>
        {/if}
        {if has_right('MEMBRE_GESTION', $user.rights) || has_right('MEMBRE_ADMIN', $user.rights) || has_right('MEMBRE_LISTER', $user.rights)}
            <li class="list_members{if $self_page == 'membres/'} current{/if}"><a href="{$www_url}admin/membres/liste.php">Liste</a></li>
        {/if}
        {if has_right('MEMBRE_ADMIN', $user.rights)}
            <li class="member_cats{if $self_page == 'membres/categories.php'} current{/if}"><a href="{$www_url}admin/membres/categories.php">Gérer les catégories de membres</a></li>
        {/if}
        <li class="logout"><a href="{$www_url}admin/logout.php">Déconnexion</a></li>
    </ul>
    {/if}
</div>

<div class="page">