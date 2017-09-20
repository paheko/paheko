{include file="admin/_head.tpl" title="Recherche par requête SQL" current="compta"}

<form method="get" action="{$admin_url}compta/operations/recherche_sql.php">
    <fieldset>
        <legend>Schéma des tables SQL</legend>
        <pre class="sql_schema">{$schema.journal}</pre>
        <dl>
            <dt><label for="f_query">Requête SQL</label></dt>
            <dd class="help">Si aucune limite n'est précisée, une limite de 100 résultats sera appliquée.</dd>
            <dd><textarea name="query" id="f_query" cols="50" rows="7" required="required">{$query}</textarea></dd>
        </dl>
        <p class="submit">
            <input type="submit" value="Exécuter &rarr;" />
        </p>
    </fieldset>
</form>

{if !empty($error)}
<p class="error">
    <strong>Erreur dans la requête SQL :</strong><br />
    {$error}
</p>
{/if}

{if !empty($result)}
<p class="alert">{$result|count} résultats renvoyés.</p>
<table class="list search">
    <thead>
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
                {foreach from=$row item="col"}
                    <td>{$col}</td>
                {/foreach}
                {if array_key_exists('id', $result[0])}
                <td class="actions">
                    {if !empty($row.id)}
                    <a class="icn" href="voir.php?id={$row.id}" title="Fiche opération">❓</a>
                    <a class="icn" href="modifier.php?id={$row.id}" title="Modifier cette opération">✎</a>
                    {/if}
                </td>
                {/if}
            </tr>
        {/foreach}
    </tbody>
</table>

{else}
<p class="alert">
    Aucun résultat trouvé.
</p>
{/if}

{include file="admin/_foot.tpl"}