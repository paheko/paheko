{if $membre}
    {include file="admin/_head.tpl" title="Enregistrer un paiement pour le membre n°`$membre.id`" current="membres/transactions" js=1}

    <ul class="actions">
        <li><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">Membre n°{$membre.id|escape}</a></li>
        <li><a href="{$admin_url}membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
        {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
            <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
        {/if}
        <li><a href="{$admin_url}membres/transactions.php?id={$membre.id|escape}">Suivi des paiements</a></li>
        <li class="current"><a href="{$admin_url}membres/transactions/ajout.php?id={$membre.id|escape}">Enregistrer un paiement</a></li>
    </ul>
{else}
    {include file="admin/_head.tpl" title="Enregistrer un paiement" current="membres/transactions" js=1}

    <ul class="actions">
        <li><a href="{$admin_url}membres/transactions/">Suivi des paiements</a></li>
        <li class="current"><a href="{$admin_url}membres/transactions/ajout.php">Saisie d'un paiement</a></li>
        <li><a href="{$admin_url}membres/transactions/rappels.php">État des rappels</a></li>
    </ul>
{/if}

{if $error}
    <p class="error">{$error|escape}</p>
{else}
    <p class="help">
        Attention cette page ne sert qu'à enregistrer les paiements effectués par les membres de l'association. Pour enregistrer une recette ou une dépense, comme le paiement d'un prestataire ou une facture, il faut <a href="{$admin_url}compta/">saisir une opération comptable</a>.
    </p>
{/if}

<form method="post" action="{$self_url|escape}">
    <fieldset>
        <legend>Enregistrer un paiement</legend>
        <dl>
            <dt><label for="f_id_transaction">Cotisation ou activité liée</label></dt>
            <dd>
                <select id="f_id_transaction" name="id_transaction">
                    <option value="0" {form_field name="id_transaction" selected=0}>-- Aucune, paiement non lié</option>
                    {foreach from=$transactions item="tr"}
                    <option value="{$tr.id|escape}" {form_field name="id_transaction" selected=$tr.id default=$default_tr} data-compta="{$tr.id_categorie_compta|escape}">
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
            <dt class="f_compta"><label for="f_moyen_paiement">Moyen de paiement</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="f_compta">
                <select name="moyen_paiement" id="f_moyen_paiement">
                {foreach from=$moyens_paiement item="moyen"}
                    <option value="{$moyen.code|escape}"{if $moyen.code == $moyen_paiement} selected="selected"{/if}>{$moyen.nom|escape}</option>
                {/foreach}
                </select>
            </dd>
            <dt class="f_cheque"><label for="f_numero_cheque">Numéro de chèque</label></dt>
            <dd class="f_cheque"><input type="text" name="numero_cheque" id="f_numero_cheque" value="{form_field name=numero_cheque}" /></dd>
            <dt class="f_banque"><label for="f_banque">Compte bancaire</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="f_banque">
                <select name="banque" id="f_banque">
                {foreach from=$comptes_bancaires item="compte"}
                    <option value="{$compte.id|escape}"{if $compte.id == $banque} selected="selected"{/if}>{$compte.libelle|escape} - {$compte.banque|escape}</option>
                {/foreach}
                </select>
            </dd>
            <dt><label for="f_montant">Montant</label></dt>
            <dd><input type="number" size="5" name="montant" id="f_montant" value="{form_field name=montant default=$default_amount}" min="0.00" step="0.01" /> {$config.monnaie|escape}</dd>
            <dt><label for="f_libelle">Libellé</label></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle}" /></dd>
            <dt><label for="f_date">Date</label></dt>
            <dd><input type="date" name="date" id="f_date" value="{form_field name=date}" /></dd>
            {if !$membre}
            <dt><label for="f_id_membre">Numéro de membre</label></dt>
            <dd><input type="number" name="id_membre" id="f_id_membre" value="{form_field name=id_membre}" step="1" min="1" /></dd>
            {/if}
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="add_transaction"}
        {if $membre}<input type="hidden" name="id_membre" value="{$membre.id|escape}" />{/if}
        <input type="submit" name="add" value="Enregistrer ce paiement &rarr;" />
    </p>
</form>

<script type="text/javascript">
{literal}
(function () {
    window.changeMoyenPaiement = function()
    {
        var elm = $('#f_moyen_paiement');
        toggleElementVisibility('.f_cheque', elm.value == 'CH');
        toggleElementVisibility('.f_banque', elm.value != 'ES');
    };

    changeMoyenPaiement();
    toggleElementVisibility('.f_compta', false);

    $('#f_moyen_paiement').onchange = changeMoyenPaiement;

    $('#f_id_transaction').onchange = function () {
        if (this.options[this.selectedIndex].getAttribute('data-compta'))
        {
            toggleElementVisibility('.f_compta', true);
            changeMoyenPaiement();
        }
        else
        {
            toggleElementVisibility('.f_compta', false);
            changeMoyenPaiement();
        }
    };
} ());
{/literal}
</script>

{include file="admin/_foot.tpl"}