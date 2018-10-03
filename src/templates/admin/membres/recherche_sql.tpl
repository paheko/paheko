{include file="admin/_head.tpl" title="Recherche par requête SQL" current="membres" js=1}

{include file="admin/membres/_nav.tpl" current="sql"}

<form method="get" action="{$admin_url}membres/recherche_sql.php">
    <fieldset>
        <legend>Schéma des tables SQL</legend>
        <pre class="sql_schema">{$schema.membres}</pre>
        <dl>
            <dt><label for="f_query">Requête SQL</label></dt>
            <dd class="help">Si aucune limite n'est précisée, une limite de 100 résultats sera appliquée.</dd>
            <dd><textarea name="query" id="f_query" cols="70" rows="7" required="required">{$query}</textarea></dd>
        </dl>
        <p class="submit">
            <input type="submit" name="run" value="Exécuter &rarr;" />
            {if $query}
                {if $id}<input type="hidden" name="id" value="{$id}" />{/if}
                <input type="submit" name="save" value="{if $id}Enregistrer : {$recherche.intitule}{else}Enregistrer cette recherche{/if}" class="minor" />
            {/if}
        </p>
    </fieldset>
</form>

{form_errors}

<form method="post" action="{$admin_url}membres/action.php" class="memberList">

{if !empty($result)}
<p class="alert">{$result|count} résultats retournés.</p>
<table class="list search">
    <thead>
        {if array_key_exists('id', $result[0])}
        <td class="check"><input type="checkbox" value="Tout cocher / décocher" onclick="g.checkUncheck();" /></td>
        {/if}
        {foreach from=$result[0] key="col" item="ignore"}
            <td>{$col}</td>
        {/foreach}
        {if array_key_exists('id', $result[0])}
        <td></td>
        {/if}
    </thead>
    <tbody>
        {foreach from=$result item="row"}
            <tr>
                {if array_key_exists('id', $result[0])}
                    <td class="check">{if !empty($row.id)}<input type="checkbox" name="selected[]" value="{$row.id}" />{/if}</td>
                {/if}
                {foreach from=$row item="col"}
                    <td>{$col}</td>
                {/foreach}
                {if array_key_exists('id', $result[0])}
                <td class="actions">
                    {if !empty($row.id)}
                    <a class="icn" href="{$admin_url}membres/fiche.php?id={$row.id}" title="Fiche membre">👤</a>
                    <a class="icn" href="{$admin_url}membres/modifier.php?id={$row.id}" title="Modifier ce membre">✎</a>
                    {/if}
                </td>
                {/if}
            </tr>
        {/foreach}
    </tbody>
    {if $session->canAccess('membres', Membres::DROIT_ADMIN)}
        {include file="admin/membres/_list_actions.tpl" colspan=count((array)$result[0])+1}
    {/if}
</table>


{else}
<p class="alert">
    Aucun membre trouvé.
</p>
{/if}

</form>

{include file="admin/_foot.tpl"}