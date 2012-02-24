{include file="admin/_head.tpl" title="`$membre.nom` (`$categorie.nom`)" current="membres"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<div class="infos">
    <h3>Informations personnelles</h3>
    <p>
        <strong>{$membre.nom|escape}</strong><br />
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
    </p>
    {if !empty($membre.telephone)}
    <p>
        Téléphone : <strong>{$membre.telephone|escape|format_tel}</strong>
    </p>
    {/if}
    {if !empty($membre.email)}
    <p>
        <a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}">Envoyer un message</a>
    </p>
    {/if}
    {if $user.droits.membres >= Garradin_Membres::DROIT_ECRITURE}
    <p>
        <a href="{$www_url}admin/membres/modifier.php?id={$membre.id|escape}">Modifier les informations de ce membre</a>
    </p>
    {/if}
</div>

<div class="infos">
    <h3>Informations générales</h3>
    <dl>
        <dt>Numéro d'adhérent</dt>
        <dd>{$membre.id|escape}</dd>
        <dt>Droits</dt>
        <dd class="droits">{format_droits droits=$categorie}</dd>
        <dt>Inscription</dt>
        <dd>{$membre.date_inscription|date_fr:'d/m/Y'}</dd>
        <dt>Dernière connexion</dt>
        <dd>{if empty($membre.date_connexion)}Jamais{else}{$membre.date_connexion|date_fr:'d/m/Y à H:i'}{/if}</dd>
    </dl>
</div>

<div class="cotisation">
    <h3>Cotisation</h3>
    {if empty($membre.date_cotisation)}
        <p class="error">Jamais réglée</p>
    {elseif $verif_cotisation === true}
        <p class="confirm">Réglée le {$membre.date_cotisation|date_fr:'d/m/Y'}</p>
    {else}
        <p class="alert">En retard de {$verif_cotisation|escape} jours</p>
    {/if}

    <form method="post" action="{$self_url}">
        <fieldset>
            <legend>Mettre à jour la cotisation</legend>
            <dl>
                <dt><label for="f_date">L'adhésion commence le...</label> (format JJ/MM/AAAA)</dt>
                <dd>
                    <input type="text" name="date" value="{form_field name=nom default=$date_cotisation_defaut}" id="f_date" />
                    {csrf_field key="cotisation_"|cat:$membre.id}
                    <input type="submit" name="cotisation" value="Enregistrer &rarr;" />
                </dd>
            </dl>
        </fieldset>
    </form>
</div>

{include file="admin/_foot.tpl"}