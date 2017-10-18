{include file="admin/_head.tpl" title="Opération n°%d"|args:$operation.id current="compta/gestion"}

{if $session->canAccess('compta', Garradin\Membres::DROIT_ADMIN)}
<ul class="actions">
    <li class="edit"><a href="{$admin_url}compta/operations/modifier.php?id={$operation.id}">Modifier cette opération</a></li>
    <li class="delete"><a href="{$admin_url}compta/operations/supprimer.php?id={$operation.id}">Supprimer cette opération</a></li>
</ul>
{/if}

<dl class="describe">
    <dt>Date</dt>
    <dd>{$operation.date|date_fr:'l j F Y (d/m/Y)'}</dd>
    <dt>Libellé</dt>
    <dd>{$operation.libelle}</dd>
    <dt>Montant</dt>
    <dd>{$operation.montant|escape|html_money}&nbsp;{$config.monnaie}</dd>
    <dt>Numéro pièce comptable</dt>
    <dd>{if trim($operation.numero_piece)}{$operation.numero_piece}{else}<em>Non renseigné</em>{/if}</dd>

    {if $operation.id_categorie}

        <dt>Moyen de paiement</dt>
        <dd>{if trim($operation.moyen_paiement)}{$moyen_paiement}{else}<em>Non renseigné</em>{/if}</dd>

        {if $operation.moyen_paiement == 'CH'}
            <dt>Numéro de chèque</dt>
            <dd>{if trim($operation.numero_cheque)}{$operation.numero_cheque}{else}<em>Non renseigné</em>{/if}</dd>
        {/if}

        {if $operation.moyen_paiement && $operation.moyen_paiement != 'ES'}
            <dt>Compte bancaire</dt>
            <dd>{$compte}</dd>
        {/if}

        <dt>Catégorie</dt>
        <dd>
            <a href="{$admin_url}compta/operations/?{if $categorie.type == Garradin\Compta\Categories::DEPENSES}depenses{else}recettes{/if}">{if $categorie.type == Garradin\Compta\Categories::DEPENSES}Dépense{else}Recette{/if}</a>&nbsp;:
            <a href="{$admin_url}compta/operations/?cat={$operation.id_categorie}">{$categorie.intitule}</a>
        </dd>
    {/if}

    <dt>Exercice</dt>
    <dd>
        <a href="{$admin_url}compta/exercices/">{$exercice.libelle}</a>
        | Du {$exercice.debut|date_fr:'d/m/Y'} au {$exercice.fin|date_fr:'d/m/Y'}
        | <strong>{if $exercice.cloture}Clôturé{else}En cours{/if}</strong>
    </dd>

    {if $operation.id_projet}
        <dt>Projet</dt>
        <dd>
            <a href="{$admin_url}compta/projets/">{$projet.libelle}</a>
        </dd>
    {/if}

    <dt>Opération créée par</dt>
    <dd>
        {if $operation.id_auteur}
            {if $session->canAccess('compta', Garradin\Membres::DROIT_ACCES)}
                <a href="{$admin_url}membres/fiche.php?id={$operation.id_auteur}">{$nom_auteur}</a>
            {else}
                {$nom_auteur}
            {/if}
        {else}
            <em>membre supprimé</em>
        {/if}
    </dd>

    <dt>Opération liée à</dt>
    <dd>
        {if empty($related_members)}
            Aucun membre n'est lié à cette opération.
        {else}
            {foreach from=$related_members item="membre"}
                <a href="{$admin_url}membres/{if $membre.id_cotisation}cotisations{else}fiche{/if}.php?id={$membre.id_membre}">{if $membre.id_cotisation}Cotisation pour {/if}{$membre.identite}</a>
            {/foreach}
        {/if}
    </dd>

    <dt>Remarques</dt>
    <dd>{if trim($operation.remarques)}{$operation.remarques}{else}Non renseigné{/if}</dd>
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
            <td><a href="{$admin_url}compta/comptes/journal.php?id={$operation.compte_debit}">{$operation.compte_debit}</a></td>
            <td>{$nom_compte_debit}</td>
            <td>{$operation.montant|escape|html_money}&nbsp;{$config.monnaie}</td>
            <td></td>
        </tr>
        <tr>
            <td><a href="{$admin_url}compta/comptes/journal.php?id={$operation.compte_credit}">{$operation.compte_credit}</a></td>
            <td>{$nom_compte_credit}</td>
            <td></td>
            <td>{$operation.montant|escape|html_money}&nbsp;{$config.monnaie}</td>
        </tr>
    </tbody>
</table>

{include file="admin/_foot.tpl"}