{include file="admin/_head.tpl" title="Liste des membres" current="membres"}

{if isset($tpl.get.sent)}
    <p class="confirm">Votre message a été envoyé.</p>
{/if}

<form method="get" action="{$self_url|escape}" class="filterCategory">
    <fieldset>
        <legend>Filtrer</legend>
        <dl>
            <dt><label for="f_cat">Catégorie</label></dt>
            <dd>
                <select name="cat" id="f_cat">
                    <option value="0" {if $current_cat == 0} selected="selected"{/if}>-- Toutes</option>
                {foreach from=$membres_cats key="id" item="nom"}
                    {if $user.droits.membres >= Garradin_Membres::DROIT_ECRITURE
                        || !array_key_exists($id, $membres_cats_cachees)}
                    <option value="{$id|escape}"{if $current_cat == $id} selected="selected"{/if}>{$nom|escape}</option>
                    {/if}
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        <input type="submit" value="Filtrer &rarr;" />
    </p>
</form>

{if $user.droits.membres >= Garradin_Membres::DROIT_ECRITURE}
    <form method="get" action="{$self_url|escape}" class="searchMember">
        <fieldset>
            <legend>Rechercher un membre</legend>
            <dl>
                <dt><label for="f_field">Dont le champ</label></dt>
                <dd>
                    <select name="search_field" id="f_field">
                        <option value="id" {if $search_field == "id"} selected="selected"{/if}>Numéro</option>
                        <option value="nom" {if $search_field == "nom"} selected="selected"{/if}>Nom et prénom</option>
                        <option value="email" {if $search_field == "email"} selected="selected"{/if}>Adresse E-Mail</option>
                        <option value="ville" {if $search_field == "ville"} selected="selected"{/if}>Ville</option>
                        <option value="code_postal" {if $search_field == "code_postal"} selected="selected"{/if}>Code postal</option>
                        <option value="adresse" {if $search_field == "adresse"} selected="selected"{/if}>Adresse postale</option>
                        <option value="telephone" {if $search_field == "telephone"} selected="selected"{/if}>Numéro de téléphone</option>
                    </select>
                </dd>
                <dt><label for="f_query">Contient</label></dt>
                <dd><input type="text" name="search_query" id="f_query" value="{$search_query|escape}" /></dd>
            </dl>
        </fieldset>

        <p class="submit">
            <input type="submit" value="Chercher &rarr;" />
        </p>
    </form>

    <form method="post" action="action.php" class="memberList">

    {if !empty($liste)}
    <table class="list">
        <thead>
            <td><input type="checkbox" value="Tout cocher / décocher" onclick="checkUncheck();" /></td>
            <td class="num" title="Numéro de membre">#</td>
            <th>Nom</th>
            <td>E-Mail</td>
            <td>Ville</td>
            <td>Cotisation</td>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="membre"}
                <tr>
                    <td>{if $user.droits.membres == Garradin_Membres::DROIT_ADMIN}<input type="checkbox" name="selected[]" value="{$membre.id|escape}" />{/if}</td>
                    <td class="num">{$membre.id|escape}</td>
                    <th>{$membre.nom|escape}</th>
                    <td>{if !empty($membre.email)}<a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}">{$membre.email|escape}</a>{/if}</td>
                    <td>
                        {$membre.ville|truncate:60|escape}
                        {if !empty($membre.code_postal)}<small>({$membre.code_postal|escape})</small>{/if}
                    </td>
                    {if empty($membre.date_cotisation)}
                        <td class="error">jamais réglée</td>
                    {elseif $membre.date_cotisation > strtotime('12 months ago')} {* FIXME durée de cotisation variable *}
                        <td class="confirm">à jour</td>
                    {else}
                        <td class="alert">en retard</td>
                    {/if}
                    <td class="actions">
                        <a href="fiche.php?id={$membre.id|escape}">Fiche</a>
                        | <a href="modifier.php?id={$membre.id|escape}">Modifier</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {if $user.droits.membres == Garradin_Membres::DROIT_ADMIN}
    <p class="checkUncheck">
        <input type="button" value="Tout cocher / décocher" onclick="checkUncheck();" />
    </p>
    <p class="actions">
        <em>Pour les membres cochés :</em>
        <input type="submit" name="move" value="Changer de catégorie" />
        <input type="submit" name="delete" value="Supprimer" />
        {csrf_field key="membres_action"}
    </p>
    {/if}

    {pagination url="?p=[ID]" page=$page bypage=$bypage total=$total}
    {else}
    <p class="alert">
        Aucune membre trouvé.
    </p>
    {/if}

    </form>

    <script type="text/javascript">
    {literal}
    (function() {
        var checked = false;

        window.checkUncheck = function()
        {
            var elements = document.getElementsByTagName('input');
            var el_length = elements.length;

            for (i = 0; i < el_length; i++)
            {
                var elm = elements[i];

                if (elm.type == 'checkbox')
                {
                    if (checked)
                        elm.checked = false;
                    else
                        elm.checked = true;
                }
            }

            checked = checked ? false : true;
            return true;
        }
    }())
    {/literal}
    </script>
{else}
    {if !empty($liste)}
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
                        {if !empty($membre.email)}<a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}">Envoyer un message</a>{/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
    {else}
    <p class="info">
        Aucune membre trouvé.
    </p>
    {/if}
{/if}

{include file="admin/_foot.tpl"}