{include file="admin/_head.tpl" title="Suivi des paiements membre n°`$membre.id`" current="membres/transactions"}

<ul class="actions">
    <li><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">Membre n°{$membre.id|escape}</a></li>
    <li><a href="{$admin_url}membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
    {/if}
    <li class="current"><a href="{$admin_url}membres/transactions.php?id={$membre.id|escape}">Suivi des paiements</a></li>
    <li><a href="{$admin_url}membres/transactions/ajout.php?id={$membre.id|escape}">Enregistrer un paiement</a></li>
</ul>

<dl class="cotisation">
{if $cotisation}
    <dt>Cotisation obligatoire</dt>
    <dd>{$cotisation.intitule|escape} — 
        {if $cotisation.duree}
            {$cotisation.duree|escape} jours
        {elseif $cotisation.debut}
            du {$cotisation.debut|format_sqlite_date_to_french} au {$cotisation.fin|format_sqlite_date_to_french}
        {else}
            ponctuelle
        {/if}
        — {$cotisation.montant|escape_money} {$config.monnaie|escape}
    </dd>
    <dt>À jour de cotisation ?</dt>
    <dd>
        {if $statut_cotisation === false}
            <span class="error"><b>Non</b>, cotisation non payée ou expirée</span>
        {elseif $statut_cotisation === -1}
            <span class="alert"><b>Non</b>, cotisation payée partiellement</span>
        {else}
            <span class="confirm"><b>Oui</b>, cotisation à jour</span>
            {if $statut_cotisation !== true}(expire le {$statut_cotisation|format_sqlite_date_to_french}){/if}
        {/if}
    </dd>
{/if}
    <dt>Paiements</dt>
    <dd>
        {if $nb_paiements == 1}
            {$nb_paiements|escape} paiement enregistré
        {elseif $nb_paiements}
            {$nb_paiements|escape} paiements enregistrés
        {else}
            Aucun paiement enregistré
        {/if} 
    </dd>
    <dd><form method="get" action="{$admin_url}membres/transactions/ajout.php"><input type="submit" value="Enregistrer un paiement &rarr;" /><input type="hidden" name="id" value="{$membre.id|escape}" /></form></dd>
</dl>

<table class="list">
    <thead>
        <th>Date</th>
        <td width="40%">Intitulé</td>
        <td>Montant</td>
        <td>Activité ou cotisation liée</td>
        <td class="actions"></td>
    </thead>
    <tbody>
        {foreach from=$paiements item="p"}
            <tr>
                <td>{$p.date|format_sqlite_date_to_french}</td>
                <td>{$p.libelle|escape}</td>
                <td class="num">{$p.montant|html_money} {$config.monnaie|escape}</td>
                <td>
                    {if $p.id_transaction}
                        {$p.intitule|escape} — 
                        {if $p.duree}
                            {$p.duree|escape} jours
                        {elseif $p.debut}
                            du {$p.debut|format_sqlite_date_to_french} au {$p.fin|format_sqlite_date_to_french}
                        {else}
                            ponctuelle
                        {/if}
                    {else}
                        <em>Aucune</em>
                    {/if}
                </td>
                <td class="actions">
                    <a class="icn" href="{$admin_url}membres/transactions/modifier.php?id={$p.id|escape}" title="Modifier">✎</a>
                    <a class="icn" href="{$admin_url}membres/transactions/supprimer.php?id={$p.id|escape}" title="Supprimer">✘</a>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>

{include file="admin/_foot.tpl"}