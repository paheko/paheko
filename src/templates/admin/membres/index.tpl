{include file="admin/_head.tpl" title="Liste des membres" current="membres"}

{include file="admin/membres/_nav.tpl" current="index"}

{if $sent}
    <p class="block confirm">Votre message a été envoyé.</p>
{/if}

{if !empty($categories)}
<form method="get" action="{$self_url}" class="shortFormRight">
    <fieldset>
        <legend>Filtrer par catégorie</legend>
        <select name="cat" id="f_cat" onchange="this.form.submit();">
            <option value="0" {if $current_cat == 0} selected="selected"{/if}>-- Toutes</option>
        {foreach from=$categories key="id" item="nom"}
            {if $session->canAccess('membres', Membres::DROIT_ECRITURE)
                || !array_key_exists($id, $hidden_categories)}
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

{if $list->count()}
    {include file="common/dynamic_list_head.tpl" check=$can_edit}

    {foreach from=$list->iterate() item="row"}
        <tr>
            {if $can_edit}
                <td class="check">{input type="checkbox" name="selected[]" value=$row._user_id}</td>
            {/if}
            {foreach from=$list->getHeaderColumns() key="key" item="value"}
                <?php $value = $row->$key; ?>
                {if $key == 'numero'}
                <td class="num">
                    <a href="{$admin_url}membres/fiche.php?id={$row._user_id}">{$value}</a>
                </td>
                {else}
                <td>
                    {if $key == $id_field}<a href="{$admin_url}membres/fiche.php?id={$row._user_id}">{/if}

                    {$value|raw|display_champ_membre:$key}

                    {if $key == $id_field}</a>{/if}
                </td>
                {/if}
            {/foreach}

            <td class="actions">
                {linkbutton label="Fiche membre" shape="user" href="!membres/fiche.php?id=%d"|args:$row._user_id}
                {if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
                    {linkbutton label="Modifier" shape="edit" href="!membres/modifier.php?id=%d"|args:$row._user_id}
                {/if}
            </td>
        </tr>
    {/foreach}

    </tbody>

    {if $session->canAccess('membres', Membres::DROIT_ADMIN)}
        {include file="admin/membres/_list_actions.tpl" colspan=count($list->getHeaderColumns())+$can_edit+1}
    {/if}

    </table>

    {pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}
{else}
    <p class="block alert">
        Aucun membre trouvé.
    </p>
{/if}

</form>

{include file="admin/_foot.tpl"}