{include file="admin/_head.tpl" title="Catégories" current="compta/categories"}

<ul class="actions">
    <li><strong><a href="{$www_url}admin/compta/exercice_ajouter.php">Créer un nouvel exercice</a></strong></li>
</ul>

    {if !empty($liste)}
        <table class="list">
        {foreach from=$liste item="exercice"}
            <tr>
                <th>{$exercice.libelle|escape}</th>
                <td>Du {$exercice.debut|date_fr:'l j F Y (d/m/Y)'} au {$exercice.fin|date_fr:'l j F Y (d/m/Y)'}</td>
                <td class="actions">
                    <a href="{$www_url}admin/compta/exercice_modifier.php?id={$exercice.id|escape}">Modifier</a>
                    | <a href="{$www_url}admin/compta/exercice_supprimer.php?id={$exercice.id|escape}">Supprimer</a>
                </td>
            </tr>
        {/foreach}
        </table>
    {else}
        <p class="alert">
            Il n'y a pas d'exercice en cours.
        </p>
    {/if}

{include file="admin/_foot.tpl"}