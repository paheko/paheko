{include file="admin/_head.tpl" title="Catégories de membres" current="membres_cats"}

<table class="list">
    <thead>
        <th>Nom</th>
        <td>Description</td>
        <td>Cotisation</td>
        <td>Droits</td>
        <td></td>
    </thead>
    <tbody>
        {foreach from=$liste item="cat"}
            <tr>
                <th>{$cat.nom|escape}</th>
                <td>{$cat.description|truncate:60|escape}</td>
                <td>{$cat.montant_cotisation|escape} € pour {$cat.duree_cotisation|escape} mois</td>
                <td class="droits">
                    {if $cat.droit_connexion >= Garradin_Membres::DROIT_ACCES}
                        <b class="acces" title="A le droit de se connecter">C</b>
                    {else}
                        <b class="aucun" title="N'a pas le droit de se connecter">C</b>
                    {/if}
                    {if $cat.droit_inscription >= Garradin_Membres::DROIT_ACCES}
                        <b class="acces" title="A le droit de s'inscrire seul">I</b>
                    {else}
                        <b class="aucun" title="N'a pas le droit de s'inscrire seul">I</b>
                    {/if}
                    {if $cat.droit_membres >= Garradin_Membres::DROIT_ADMIN}
                        <b class="admin" title="Membres : administrateur">M</b>
                    {elseif $cat.droit_membres >= Garradin_Membres::DROIT_ACCES}
                        <b class="acces" title="Membres : accès à la liste">M</b>
                    {/if}
                    {if $cat.droit_compta >= Garradin_Membres::DROIT_ADMIN}
                        <b class="admin" title="Compta : administrateur">€</b>
                    {elseif $cat.droit_compta >= Garradin_Membres::DROIT_ACCES}
                        <b class="acces" title="Compta : peut voir les comptes">€</b>
                    {/if}
                    {if $cat.droit_wiki >= Garradin_Membres::DROIT_ADMIN}
                        <b class="admin" title="Wiki : administrateur">W</b>
                    {elseif $cat.droit_wiki}
                        <b class="acces" title="Wiki : accès en lecture">W</b>
                    {/if}
                    {if $cat.droit_config >= Garradin_Membres::DROIT_ADMIN}
                        <b class="admin" title="Peut modifier la configuration">&#x2611;</b>
                    {else}
                        <b class="aucun" title="Ne peut modifier la configuration">&#x2611;</b>
                    {/if}
                </td>
                <td class="actions">
                    <a href="cat_modifier.php?id={$cat.id|escape}">Modifier</a>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>

{include file="admin/_foot.tpl"}