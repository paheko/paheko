{include file="admin/_head.tpl" title="Recherche par requête SQL" current="membres"}

<form method="get" action="{$admin_url}membres/recherche_sql.php">
    <fieldset>
        <legend>Schéma des tables SQL</legend>
        <pre class="sql_schema">{$schema.membres|escape}</pre>
        <dl>
            <dt><label for="f_query">Requête SQL</label></dt>
            <dd class="help">Si aucune limite n'est précisée, une limite de 100 résultats sera appliquée.</dd>
            <dd><textarea name="query" id="f_query" cols="50" rows="7">{$query|escape}</textarea></dd>
        </dl>
        <p class="submit">
            <input type="submit" value="Exécuter &rarr;" />
        </p>
    </fieldset>
</form>

{if !empty($error)}
<p class="error">
    <strong>Erreur dans la requête SQL :</strong><br />
    {$error|escape}
</p>
{/if}

<form method="post" action="{$admin_url}membres/action.php" class="memberList">

{if !empty($result)}
<table class="list search">
    <thead>
        {if array_key_exists('id', $result[0])}
        <td class="check"><input type="checkbox" value="Tout cocher / décocher" onclick="checkUncheck();" /></td>
        {/if}
        {foreach from=$result[0] key="col" item="ignore"}
            <td>{$col|escape}</td>
        {/foreach}
        {if array_key_exists('id', $result[0])}
        <td></td>
        {/if}
    </thead>
    <tbody>
        {foreach from=$result item="row"}
            <tr>
                {if array_key_exists('id', $result[0])}
                    <td class="check">{if !empty($row.id)}<input type="checkbox" name="selected[]" value="{$row.id|escape}" />{/if}</td>
                {/if}
                {foreach from=$row item="col"}
                    <td>{$col|escape}</td>
                {/foreach}
                {if array_key_exists('id', $result[0])}
                <td class="actions">
                    {if !empty($row.id)}
                    <a class="icn" href="fiche.php?id={$row.id|escape}" title="Fiche membre">❓</a>
                    <a class="icn" href="modifier.php?id={$row.id|escape}" title="Modifier ce membre">✎</a>
                    {/if}
                </td>
                {/if}
            </tr>
        {/foreach}
    </tbody>
</table>

<p class="checkUncheck">
    <input type="button" value="Tout cocher / décocher" onclick="checkUncheck();" />
</p>
<p class="actions">
    <em>Pour les membres cochés :</em>
    <input type="submit" name="move" value="Changer de catégorie" />
    <input type="submit" name="delete" value="Supprimer" />
    {csrf_field key="membres_action"}
</p>

{elseif !is_null($result)}
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

{include file="admin/_foot.tpl"}