{include file="admin/_head.tpl" title="Rappels pour cotisations du membre" current="membres/cotisations" js=1}

<ul class="actions">
    <li><a href="{$admin_url}membres/fiche.php?id={$membre.id}"><b>{$membre.identite}</b></a></li>
    <li><a href="{$admin_url}membres/modifier.php?id={$membre.id}">Modifier</a></li>
    {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN) && $user.id != $membre.id}
        <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id}">Supprimer</a></li>
    {/if}
    <li class="current"><a href="{$admin_url}membres/cotisations.php?id={$membre.id}">Suivi des cotisations</a></li>
</ul>

<form method="post" action="{$self_url}">
    <fieldset>
        <legend>Enregistrer un rappel fait à ce membre</legend>
        <dl>
            <dt><label for="f_id_cotisation">Cotisation</label></dt>
            <dd>
                <select id="f_id_cotisation" name="id_cotisation">
                {foreach from=$cotisations item="co"}
                    <option value="{$co.id}">{$co.intitule} — 
                    {if $co.a_jour}
                        Expire dans {$co.nb_jours} jours
                    {else}
                        EXPIRÉE depuis {$co.nb_jours} jours
                    {/if}
                    </option>
                {/foreach}
                </select>
            </dd>
            <dt><label for="f_date">Date du rappel</label></dt>
            <dd><input type="date" name="date" id="f_date" required="required" value="{form_field name="date" default=$default_date}" /></dd>
            <dt><label for="f_media_email">Moyen de communication utilisé</label></dt>
            <dd>
                <label>
                    <input id="f_media_email" type="radio" name="media" value="{$rappels_envoyes::MEDIA_EMAIL}" />
                    E-Mail
                </label>
            </dd>
            {* FIXME: proposer d'envoyer un email au membre *}
            <dd>
                <label>
                    <input type="radio" name="media" value="{$rappels_envoyes::MEDIA_TELEPHONE}" />
                    Téléphone
                </label>
            </dd>
            {* FIXME: afficher les différents numéros de téléphone de la fiche membre *}
            <dd>
                <label>
                    <input type="radio" name="media" value="{$rappels_envoyes::MEDIA_COURRIER}" />
                    Courrier postal
                </label>
            </dd>
            <dd>
                <label>
                    <input type="radio" name="media" value="{$rappels_envoyes::MEDIA_AUTRE}" />
                    Autre
                </label>
            </dd>
        </dl>
        <p class="submit">
            {csrf_field key="add_rappel_%s"|args:$membre.id}
            <input type="submit" name="save" value="Enregistrer le rappel &rarr;" />
        </p>
    </fieldset>
</form>

{if !empty($rappels)}
<table class="list">
    <thead>
        <th>Date du rappel</th>
        <td>Moyen de communication</td>
        <td>Cotisation</td>
        <td class="actions"></td>
    </thead>
    <tbody>
        {foreach from=$rappels item="r"}
            <tr>
                <th>{$r.date|format_sqlite_date_to_french}</th>
                <td>
                    {if $r.media == Garradin\Rappels_envoyes::MEDIA_AUTRE}
                        Autre
                    {elseif $r.media == Garradin\Rappels_envoyes::MEDIA_COURRIER}
                        Courrier
                    {elseif $r.media == Garradin\Rappels_envoyes::MEDIA_TELEPHONE}
                        Téléphone
                    {else}
                        E-Mail
                    {/if}
                </td>
                <td>
                    {$r.intitule} — 
                    {$r.montant|escape|html_money} {$config.monnaie}
                </td>
                <td class="actions">
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>
{/if}

{include file="admin/_foot.tpl"}