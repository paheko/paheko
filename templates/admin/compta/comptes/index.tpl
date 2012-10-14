{if empty($classe)}
    {include file="admin/_head.tpl" title="Comptes" current="compta/categories"}
    <ul class="accountList">
    {foreach from=$classes item="_classe"}
        <li><h4><a href="{$www_url}admin/compta/comptes/?classe={$_classe.id|escape}">{$_classe.libelle|escape}</a></h4></li>
    {/foreach}
    </ul>
{else}
    {include file="admin/_head.tpl" title=$classe_compte.libelle current="compta/categories"}

    <ul class="actions">
        <li><a href="{$www_url}admin/compta/comptes/">Liste des classes</a></li>
        <li><a href="{$www_url}admin/compta/comptes/ajouter.php?classe={$classe|escape}">Ajouter un compte dans cette classe</a></li>
    </ul>

    <p class="help">
        Les comptes avec la mention <em>*</em> font partie du plan comptable standard
        et ne peuvent être modifiés ou supprimés.
    </p>

    {if !empty($liste)}
        <table class="list accountList">
        {foreach from=$liste item="compte"}
            <tr class="niveau_{$compte.id|strlen}">
                <th>{$compte.id|escape}</th>
                <td class="libelle">{$compte.libelle|escape}</td>
                <td class="actions">
                    {$compte.position|get_position}
                    {if !$compte.plan_comptable}
                        | <a href="{$www_url}admin/compta/comptes/modifier.php?id={$compte.id|escape}">Modifier</a>
                        | <a href="{$www_url}admin/compta/comptes/supprimer.php?id={$compte.id|escape}">Supprimer</a>
                    {else}
                        <em>*</em>
                    {/if}
                </td>
            </tr>
        {/foreach}
        </table>

    {else}
        <p class="alert">
            Aucun compte trouvé.
        </p>
    {/if}
{/if}

{include file="admin/_foot.tpl"}