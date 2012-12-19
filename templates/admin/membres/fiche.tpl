{include file="admin/_head.tpl" title="`$membre.nom` (`$categorie.nom`)" current="membres"}

{if $user.droits.membres >= Garradin\Membres::DROIT_ECRITURE}
<ul class="actions">
    <li><a href="{$www_url}admin/membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$www_url}admin/membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
    {/if}
</ul>
{/if}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<dl class="describe">
    <dt>Numéro d'adhérent</dt>
    <dd>{$membre.id|escape}</dd>
    <dt>Nom et prénom</dt>
    <dd><strong>{$membre.nom|escape}</strong></dd>
    <dt>Adresse</dt>
    <dd>
        {if !empty($membre.adresse) || !empty($membre.code_postal) || !empty($membre.ville) || !empty($membre.pays)}
            {if !empty($membre.adresse)}
                {$membre.adresse|escape|nl2br}<br />
            {/if}
            {if !empty($membre.code_postal)}
                {$membre.code_postal|escape}
            {/if}
            {if !empty($membre.ville)}
                {$membre.ville|escape}<br />
            {/if}
            ({$membre.pays|get_country_name|escape})
        {else}
            <em>(Non renseignée)</em>
        {/if}
    </dd>
    <dt>Téléphone</dt>
    <dd>
        {if !empty($membre.telephone)}
            <a href="tel:{$membre.telephone|escape}">{$membre.telephone|escape|format_tel}</a>
        {else}
            <em>(Non renseigné)</em>
        {/if}
    <dt>Adresse E-Mail</dt>
    <dd>
        {if !empty($membre.email)}
            <a href="mailto:{$membre.email|escape}">{$membre.email|escape}</a>
            | <a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}">Envoyer un message</a>
            {if $membre.lettre_infos}
                <em>(inscrit-e à la lettre d'informations)</em>
            {/if}
        {else}
            <em>(Non renseignée)</em>
        {/if}
    </dd>
    <dt>Catégorie</dt>
    <dd>{$categorie.nom|escape} <span class="droits">{format_droits droits=$categorie}</span></dd>
    <dt>Inscription</dt>
    <dd>{$membre.date_inscription|date_fr:'d/m/Y'}</dd>
    <dt>Dernière connexion</dt>
    <dd>{if empty($membre.date_connexion)}Jamais{else}{$membre.date_connexion|date_fr:'d/m/Y à H:i'}{/if}</dd>
    <dt>Mot de passe</dt>
    <dd>{if empty($membre.passe)}Non{else}Oui{/if}</dd>
    <dt>Cotisation</dt>
    <dd>
    {if empty($membre.date_cotisation)}
        <span class="error">Jamais réglée</span>
    {elseif $verif_cotisation === true}
        <span class="confirm">Réglée le {$membre.date_cotisation|date_fr:'d/m/Y'}</span>
    {else}
        <span class="alert">En retard de {$verif_cotisation|escape} jours</span>
    {/if}
    </dd>
    <dd>
        <form method="post" action="{$self_url}">
            <fieldset>
                <legend>Mettre à jour la cotisation</legend>
                <dl>
                    <dt><label for="f_date">L'adhésion commence le...</label></dt>
                    <dd>
                        <input type="date" name="date" value="{form_field name=nom default=$date_cotisation_defaut}" id="f_date" />
                        {csrf_field key="cotisation_"|cat:$membre.id}
                        <input type="submit" name="cotisation" value="Enregistrer &rarr;" />
                    </dd>
                </dl>
            </fieldset>
        </form>
    </dd>
</dl>

{include file="admin/_foot.tpl"}