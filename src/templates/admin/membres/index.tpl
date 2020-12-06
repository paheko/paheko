{include file="admin/_head.tpl" title="Liste des membres" current="membres"}

{include file="admin/membres/_nav.tpl" current="index"}

{if $sent}
    <p class="block confirm">Votre message a été envoyé.</p>
{/if}

{if !empty($membres_cats)}
<form method="get" action="{$self_url}" class="shortFormRight">
    <fieldset>
        <legend>Filtrer par catégorie</legend>
        <select name="cat" id="f_cat" onchange="this.form.submit();">
            <option value="0" {if $current_cat == 0} selected="selected"{/if}>-- Toutes</option>
        {foreach from=$membres_cats key="id" item="nom"}
            {if $session->canAccess('membres', Membres::DROIT_ECRITURE)
                || !array_key_exists($id, $membres_cats_cachees)}
            <option value="{$id}"{if $current_cat == $id} selected="selected"{/if}>{$nom}</option>
            {/if}
        {/foreach}
        </select>
        <noscript><input type="submit" value="Filtrer &rarr;" /></noscript>
    </fieldset>
</form>
{/if}

<form method="get" action="{$admin_url}membres/recherche.php" class="shortFormLeft">
    <fieldset>
        <legend>Rechercher un membre</legend>
        <input type="text" name="qt" value="" />
        <input type="submit" value="Chercher &rarr;" />
    </fieldset>
</form>

<form method="post" action="action.php" class="memberList">

{if !empty($liste)}
    <table class="list">
        <thead class="userOrder">
            <tr>
                {if $session->canAccess('membres', Membres::DROIT_ADMIN)}<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>{/if}
                {foreach from=$champs key="c" item="champ"}
                    <td class="{if $order == $c} cur {if $desc}desc{else}asc{/if}{/if}">{if $c == "numero"}Num.{else}{$champ.title}{/if} <a href="?o={$c}&amp;a&amp;cat={$current_cat}" class="icn up">&uarr;</a><a href="?o={$c}&amp;d&amp;cat={$current_cat}" class="icn dn">&darr;</a></td>
                {/foreach}
                <td></td>
            </tr>
        </thead>
        <tbody>
            {foreach from=$liste item="membre"}
                <tr>
                    {if $session->canAccess('membres', Membres::DROIT_ADMIN)}<td class="check">{input type="checkbox" name="selected[]" value=$membre.id}</td>{/if}
                    {foreach from=$champs key="c" item="cfg"}
                        <td>
                            {if $c == $config.champ_identite}<a href="{$admin_url}membres/fiche.php?id={$membre.id}">{/if}
                            {$membre->$c|raw|display_champ_membre:$cfg}
                            {if $c == $config.champ_identite}</a>{/if}
                        </td>
                    {/foreach}
                    <td class="actions">
                        {linkbutton label="Fiche membre" shape="user" href="!membres/fiche.php?id=%d"|args:$membre.id}
                        {if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
                            {linkbutton label="Modifier" shape="edit" href="!membres/modifier.php?id=%d"|args:$membre.id}
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    {if $session->canAccess('membres', Membres::DROIT_ADMIN)}
        {include file="admin/membres/_list_actions.tpl" colspan=count((array)$champs)+1}
    {/if}
    </table>

    {pagination url=$pagination_url page=$page bypage=$bypage total=$total}
{else}
    <p class="block alert">
        Aucun membre trouvé.
    </p>
{/if}

</form>

{include file="admin/_foot.tpl"}