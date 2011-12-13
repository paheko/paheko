{include file="admin/_head.tpl" title="Liste des membres"}

<form method="get" action="{$self_url|escape}">
    <fieldset>
        <legend>Filtre</legend>
        <dl>
            <dt><label for="f_cat">Catégorie</label></dt>
            <dd>
                <select name="cat" id="f_cat">
                    <option value="0" {if $current_cat == 0} selected="selected"{/if}>-- Toutes</option>
                {foreach from=$membres_cats key="id" item="nom"}
                    <option value="{$id|escape}"{if $current_cat == $id} selected="selected"{/if}>{$nom|escape}</option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        <input type="submit" value="Filtrer &rarr;" />
    </p>
</form>

{if $user.droits >= Garradin_Membres::DROIT_ADMIN}
<table class="list">
    <thead>
        <th>Nom</th>
        <td>E-Mail</td>
        <td>Ville</td>
        <td>Cotisation</td>
        <td></td>
    </thead>
    <tbody>
        {foreach from=$liste item="membre"}
            <tr>
                <th>{$membre.nom|escape}</th>
                <td><a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}">{$membre.email|escape}</a></td>
                <td>
                    {$membre.ville|truncate:60|escape}
                    {if !empty($membre.code_postal)}<small>({$membre.code_postal|escape})</small>{/if}
                </td>
                {if empty($membre.date_cotisation)}
                    <td class="alert">jamais réglée</td>
                {else}
                    <td>{$membre.date_cotisation|escape}</td>
                {/if}
                <td class="actions">
                    <a href="modifier.php?id={$membre.id|escape}">Modifier</a>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>
{else}
<table class="list">
    <thead>
        <th>Nom</th>
        <td></td>
    </thead>
    <tbody>
        {foreach from=$liste item="membre"}
            <tr>
                <th>{$membre.nom|escape}</th>
                <td class="actions">
                    <a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}">Envoyer un message</a>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>
{/if}

{include file="admin/_foot.tpl"}