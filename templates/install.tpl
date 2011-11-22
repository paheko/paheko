<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <meta charset="utf-8" />
    <title>Garradin - Installation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="{$www_url}style/admin.css" media="screen,projection,handheld" />
</head>

<body>

<h1>Installation de Garradin</h1>

{if $disabled}
    <p class="error">Garradin est déjà installé.</p>
{else}
    <p class="intro">
        Bienvenue dans Garradin !
        Veuillez remplir les quelques informations suivantes pour terminer
        l'installation.
    </p>

    {if !empty($error)}
        <p class="error">{$error|escape}</p>
    {/if}

    <form method="post" action="{$self_url}">

    <fieldset>
        <legend>Informations sur l'association</legend>
        <dl>
            <dt><label for="f_nom_asso">Nom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom_asso" id="f_nom_asso" value="{if !empty($tpl.post.nom_asso)}{$tpl.post.nom_asso|escape}{/if}" /></dd>
            <dt><label for="f_email_asso">Adresse E-Mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="email" name="email_asso" id="f_email_asso" value="{if !empty($tpl.post.email_asso)}{$tpl.post.email_asso|escape}{/if}" /></dd>
            <dt><label for="f_adresse_asso">Adresse postale</label></dt>
            <dd><textarea cols="50" rows="5" name="adresse_asso" id="f_adresse_asso">{if !empty($tpl.post.adresse_asso)}{$tpl.post.adresse_asso|escape}{/if}</textarea></dd>
            <dt><label for="f_site_asso">Site web</label></dt>
            <dd><input type="url" name="site_asso" id="f_site_asso" value="{if !empty($tpl.post.site_asso)}{$tpl.post.site_asso|escape}{/if}" /></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Informations sur le premier membre</legend>
        <dl>
            <dt><label for="f_nom_membre">Nom et prénom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom_membre" id="f_nom_membre" value="{if !empty($tpl.post.nom_membre)}{$tpl.post.nom_membre|escape}{/if}" /></dd>
            <dt><label for="f_cat_membre">Catégorie du membre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="tip">Par exemple : bureau, conseil d'administration, présidente, trésorier, etc.</dd>
            <dd><input type="text" name="cat_membre" id="f_cat_membre" value="{if !empty($tpl.post.cat_membre)}{$tpl.post.cat_membre|escape}{/if}" /></dd>
            <dt><label for="f_email_membre">Adresse E-Mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="email" name="email_membre" id="f_email_membre" value="{if !empty($tpl.post.email_membre)}{$tpl.post.email_membre|escape}{/if}" /></dd>
            <dt><label for="f_passe_membre">Mot de passe</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="password" name="passe_membre" id="f_passe_membre" value="{if !empty($tpl.post.passe_membre)}{$tpl.post.passe_membre|escape}{/if}" /></dd>
            <dt><label for="f_repasse_membre">Encore le mot de passe</label> (vérification) <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="password" name="repasse_membre" id="f_repasse_membre" value="{if !empty($tpl.post.repasse_membre)}{$tpl.post.repasse_membre|escape}{/if}" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="install"}
        <input type="submit" name="save" value="Terminer l'installation &rarr;" />
    </p>

    </form>
{/if}

</body>
</html>