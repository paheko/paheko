{include file="admin/_head.tpl" title="Opération n°`$operation.id`" current="compta/gestion"}

<dl class="operation">
    <dt>Date</dt>
    <dd>{$operation.date|date_fr:'l j F Y (d/m/Y)'}</dd>
    <dt>Libellé</dt>
    <dd>{$operation.libelle|escape}</dd>
    <dt>Montant</dt>
    <dd>{$operation.montant|escape}&nbsp;{$config.monnaie|escape}</dd>
    <dt>Numéro pièce comptable</dt>
    <dd>{if trim($operation.numero_piece)}{$operation.numero_piece|escape}{else}Non renseigné{/if}</dd>

    {if $operation.id_categorie}

        <dt>Moyen de paiement</dt>
        <dd>{if trim($operation.moyen_paiement)}{$operation.moyen_paiement|escape}{else}Non renseigné{/if}</dd>

        {if $operation.moyen_paiement == 'CH'}
            <dt>Numéro de chèque</dt>
            <dd>{if trim($operation.numero_cheque)}{$operation.numero_cheque|escape}{else}Non renseigné{/if}</dd>
        {/if}

        {if $operation.moyen_paiement && $operation.moyen_paiement != 'ES'}
            <dt>Compte bancaire</dt>
            <dd>{$compte|escape}</dd>
        {/if}

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
            <th>Comptes</th>
            <td>Débit</td>
            <td>Crédit</td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{$operation.compte_debit|escape} - {$nom_compte_debit}</td>
            <td>{$operation.montant|escape}&nbsp;{$config.monnaie|escape}</td>
            <td></td>
        </tr>
        <tr>
            <td>{$operation.compte_credit|escape} - {$nom_compte_credit}</td>
            <td></td>
            <td>{$operation.montant|escape}&nbsp;{$config.monnaie|escape}</td>
        </tr>
    </tbody>
</table>

{include file="admin/_foot.tpl"}