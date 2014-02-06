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
        {if $cotisation.total === null}
            <span class="error"><b>Non</b>, cotisation non payée ou expirée</span>
        {elseif $cotisation.a_payer > 0}
            <span class="alert"><b>Non</b>, cotisation payée partiellement</span>
        {else}
            <span class="confirm"><b>Oui</b>, cotisation à jour</span>
            {if $cotisation.expiration}(expire le {$cotisation.expiration|format_sqlite_date_to_french}){/if}
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
{if !empty($activites)}
    <dt>Activités ou cotisations en cours</dt>
    {foreach from=$activites item="activite"}
    <dd>{$activite.intitule|escape} — 
        {if $activite.total >= $activite.montant}<span class="confirm">Réglé</span>
        {else}
            <span class="alert">{$activite.total|escape_money} {$config.monnaie|escape} 
                réglés sur un total de {$activite.montant|escape_money} {$config.monnaie|escape}</span>
            <span class="error">(reste {$activite.a_payer|escape_money} {$config.monnaie|escape} à payer)</span>
        {/if}
        {if $activite.expiration}— Valide jusqu'au {$activite.expiration|format_sqlite_date_to_french}{/if}
    </dd>
    {/foreach}
{/if}
</dl>

{if !empty($paiements)}
<table class="list">
    <thead>
        <th>Date</th>
        <td>Intitulé</td>
        <td>Montant</td>
        <td>Activité ou cotisation liée</td>
        <td>Écritures liées</td>
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
                <td class="num">
                    {if $p.nb_operations > 0}
                    <a href="{$admin_url}membres/transactions/ecritures.php?id={$p.id|escape}">{$p.nb_operations|escape}</a>
                    {else}
                    0
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
{/if}

{include file="admin/_foot.tpl"}