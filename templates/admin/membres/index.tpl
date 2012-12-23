{include file="admin/_head.tpl" title="Liste des membres" current="membres"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/">Liste des membres</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ECRITURE}
        <li><a href="{$admin_url}membres/recherche.php">Recherche avancée</a></li>
    {/if}
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$admin_url}membres/export.php">Export de la liste en CSV</a></li>
    {/if}

</ul>

{if isset($tpl.get.sent)}
    <p class="confirm">Votre message a été envoyé.</p>
{/if}

<form method="get" action="{$self_url|escape}" class="filterCategory">
    <fieldset>
        <legend>Filtrer par catégorie</legend>
        <select name="cat" id="f_cat">
            <option value="0" {if $current_cat == 0} selected="selected"{/if}>-- Toutes</option>
        {foreach from=$membres_cats key="id" item="nom"}
            {if $user.droits.membres >= Garradin\Membres::DROIT_ECRITURE
                || !array_key_exists($id, $membres_cats_cachees)}
            <option value="{$id|escape}"{if $current_cat == $id} selected="selected"{/if}>{$nom|escape}</option>
            {/if}
        {/foreach}
        </select>
        <input type="submit" value="Filtrer &rarr;" />
    </fieldset>
</form>

<form method="get" action="{$self_url|escape}" class="searchMember">
    <fieldset>
        <legend>Rechercher un membre</legend>
        <input type="text" name="search_query" value="{$search_query|escape}" />
        <input type="submit" value="Chercher &rarr;" />
    </fieldset>
</form>

{if $user.droits.membres >= Garradin\Membres::DROIT_ECRITURE}

    <form method="post" action="action.php" class="memberList">

    {if !empty($liste)}
    <table class="list">
        <thead>
            <td class="check"><input type="checkbox" value="Tout cocher / décocher" onclick="checkUncheck();" /></td>
            {if !empty($order)}
                <td class="num{if $order == 'id'} cur {if $desc}desc{else}asc{/if}{/if}" title="Numéro de membre"># <a href="?o=id&amp;a">&darr;</a><a href="?o=id&amp;d">&uarr;</a></td>
                <th class="{if $order == 'nom'}cur {if $desc}desc{else}asc{/if}{/if}">Nom <a href="?o=nom&amp;a">&darr;</a><a href="?o=nom&amp;d">&uarr;</a></th>
                <td class="{if $order == 'date_cotisation'}cur {if $desc}desc{else}asc{/if}{/if}">Cotisation <a href="?o=date_cotisation&amp;a">&darr;</a><a href="?o=date_cotisation&amp;d">&uarr;</a></td>
                <td class="{if $order == 'date_inscription'}cur {if $desc}desc{else}asc{/if}{/if}">Inscription <a href="?o=date_inscription&amp;a">&darr;</a><a href="?o=date_inscription&amp;d">&uarr;</a></td>
                <td class="{if $order == 'ville'}cur {if $desc}desc{else}asc{/if}{/if}">Ville <a href="?o=ville&amp;a">&darr;</a><a href="?o=ville&amp;d">&uarr;</a></td>
            {else}
                <td title="Numéro de membre">#</td>
                <th>Nom</th>
                <td>Cotisation</td>
                <td>Inscription</td>
                <td>Ville</td>
            {/if}
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="membre"}
                <tr>
                    <td class="check">{if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}<input type="checkbox" name="selected[]" value="{$membre.id|escape}" />{/if}</td>
                    <td class="num"><a title="Fiche membre" href="{$www_url}admin/membres/fiche.php?id={$membre.id|escape}">{$membre.id|escape}</a></td>
                    <th>{$membre.nom|escape}</th>
                    {if empty($membre.date_cotisation)}
                        <td class="error">jamais réglée</td>
                    {elseif $membre.date_cotisation > strtotime('12 months ago')} {* FIXME durée de cotisation variable *}
                        <td class="confirm">à jour</td>
                    {else}
                        <td class="alert">en retard</td>
                    {/if}
                    <td>{$membre.date_inscription|date_fr:'d/m/Y'}</td>
                    <td>{$membre.ville|escape}</td>
                    <td class="actions">
                        {if !empty($membre.email)}<a class="icn" href="{$www_url}admin/membres/message.php?id={$membre.id|escape}" title="Envoyer un message">✉</a> {/if}
                        <a class="icn" href="modifier.php?id={$membre.id|escape}">✎</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}
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

    {pagination url=$pagination_url page=$page bypage=$bypage total=$total}
    {else}
    <p class="alert">
        Aucun membre trouvé.
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