{include file="admin/_head.tpl" title="Opération n°`$operation.id`" current="compta/gestion"}

{if $user.droits.compta >= Garradin_Membres::DROIT_ADMIN}
<ul class="actions">
    <li class="edit"><a href="{$admin_url}compta/operations/modifier.php?id={$operation.id|escape}">Modifier cette opération</a></li>
    <li class="delete"><a href="{$admin_url}compta/operations/supprimer.php?id={$operation.id|escape}">Supprimer cette opération</a></li>
</ul>
{/if}

<dl class="describe">
    <dt>Date</dt>
    <dd>{$operation.date|date_fr:'l j F Y (d/m/Y)'}</dd>
    <dt>Libellé</dt>
    <dd>{$operation.libelle|escape}</dd>
    <dt>Montant</dt>
    <dd>{$operation.montant|escape_money}&nbsp;{$config.monnaie|escape}</dd>
    <dt>Numéro pièce comptable</dt>
    <dd>{if trim($operation.numero_piece)}{$operation.numero_piece|escape}{else}<em>Non renseigné</em>{/if}</dd>

    {if $operation.id_categorie}

        <dt>Moyen de paiement</dt>
        <dd>{if trim($operation.moyen_paiement)}{$moyen_paiement|escape}{else}<em>Non renseigné</em>{/if}</dd>

        {if $operation.moyen_paiement == 'CH'}
            <dt>Numéro de chèque</dt>
            <dd>{if trim($operation.numero_cheque)}{$operation.numero_cheque|escape}{else}<em>Non renseigné</em>{/if}</dd>
        {/if}

        {if $operation.moyen_paiement && $operation.moyen_paiement != 'ES'}
            <dt>Compte bancaire</dt>
            <dd>{$compte|escape}</dd>
        {/if}

        <dt>Catégorie</dt>
        <dd>
            <a href="{$www_url}admin/compta/operations/?{if $categorie.type == Garradin_Compta_Categories::DEPENSES}depenses{else}recettes{/if}">{if $categorie.type == Garradin_Compta_Categories::DEPENSES}Dépense{else}Recette{/if}</a>&nbsp;:
            <a href="{$www_url}admin/compta/operations/?cat={$operation.id_categorie|escape}">{$categorie.intitule|escape}</a>
        </dd>
    {/if}

    <dt>Opération créée par</dt>
    <dd>
        {if $user.droits.membres >= Garradin_Membres::DROIT_ACCES}
            <a href="{$www_url}admin/membres/fiche.php?id={$operation.id_auteur|escape}">{$nom_auteur|escape}</a>
        {else}
            {$nom_auteur|escape}
        {/if}
    </dd>

    <dt>Remarques</dt>
    <dd>{if trim($operation.remarques)}{$operation.remarques|escape}{else}Non renseigné{/if}</dd>
</dl>

<table class="list multi">
    <thead>
        <tr>
            <th colspan="2">Comptes</th>
            <td>Débit</td>
            <td>Crédit</td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><a href="{$admin_url}compta/comptes/journal.php?id={$operation.compte_debit|escape}">{$operation.compte_debit|escape}</a></td>
            <td>{$nom_compte_debit}</td>
            <td>{$operation.montant|escape_money}&nbsp;{$config.monnaie|escape}</td>
            <td></td>
        </tr>
        <tr>
            <td><a href="{$admin_url}compta/comptes/journal.php?id={$operation.compte_credit|escape}">{$operation.compte_credit|escape}</a></td>
            <td>{$nom_compte_credit}</td>
            <td></td>
            <td>{$operation.montant|escape_money}&nbsp;{$config.monnaie|escape}</td>
        </tr>
    </tbody>
</table>

{include file="admin/_foot.tpl"}