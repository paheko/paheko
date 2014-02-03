{if $membre}
    {include file="admin/_head.tpl" title="Modifier un paiement pour le membre n°`$membre.id`" current="membres/transactions" js=1}

    <ul class="actions">
        <li><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">Membre n°{$membre.id|escape}</a></li>
        <li><a href="{$admin_url}membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
        {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
            <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
        {/if}
        <li><a href="{$admin_url}membres/transactions.php?id={$membre.id|escape}">Suivi des paiements</a></li>
        <li><a href="{$admin_url}membres/transactions/ajout.php?id={$membre.id|escape}">Enregistrer un paiement</a></li>
    </ul>
{else}
    {include file="admin/_head.tpl" title="Modifier un paiement" current="membres/transactions" js=1}

    <ul class="actions">
        <li><a href="{$admin_url}membres/transactions/">Suivi des paiements</a></li>
        <li><a href="{$admin_url}membres/transactions/ajout.php">Saisie d'un paiement</a></li>
        <li><a href="{$admin_url}membres/transactions/rappels.php">État des rappels</a></li>
    </ul>
{/if}

{if $error}
    <p class="error">{$error|escape}</p>
{/if}

<form method="post" action="{$self_url|escape}">
    <fieldset>
        <legend>Modifier un paiement</legend>
        <dl>
            <dt><label for="f_id_transaction">Cotisation ou activité liée</label></dt>
            <dd>
                <select id="f_id_transaction" name="id_transaction">
                    <option value="0" {form_field name="id_transaction" selected=0 data=$transaction}>-- Aucune, paiement non lié</option>
                    {foreach from=$transactions item="tr"}
                    <option value="{$tr.id|escape}" {form_field name="id_transaction" selected=$tr.id data=$transaction}>
                        {$tr.intitule|escape}
                        — {$tr.montant|html_money} {$config.monnaie|escape}
                        — {if $tr.duree}pour {$tr.duree|escape} jours
                        {elseif $tr.debut}
                            du {$tr.debut|format_sqlite_date_to_french} au {$tr.fin|format_sqlite_date_to_french}
                        {else}
                            ponctuelle
                        {/if}
                    </option>
                    {/foreach}
                </select>
            </dd>
            <dd class="help">
                Un paiement non relié à une activité ou cotisation peut être
                par exemple un don ponctuel.
            </dd>
            <dt><label for="f_montant">Montant</label></dt>
            <dd><input type="number" size="5" name="montant" id="f_montant" value="{form_field name=montant data=$transaction}" min="0.00" step="0.01" /> {$config.monnaie|escape}</dd>
            <dt><label for="f_libelle">Libellé</label></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle data=$transaction}" /></dd>
            <dt><label for="f_date">Date</label></dt>
            <dd><input type="date" name="date" id="f_date" value="{form_field name=date data=$transaction}" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="edit_transaction_`$transaction.id`"}
        <input type="submit" name="edit" value="Enregistrer &rarr;" />
    </p>
</form>


{include file="admin/_foot.tpl"}