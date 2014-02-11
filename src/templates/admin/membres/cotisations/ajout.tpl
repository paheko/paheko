{if $membre}
    {include file="admin/_head.tpl" title="Enregistrer une cotisation pour le membre n°`$membre.id`" current="membres/cotisations" js=1}

    <ul class="actions">
        <li><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">Membre n°{$membre.id|escape}</a></li>
        <li><a href="{$admin_url}membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
        {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
            <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
        {/if}
        <li><a href="{$admin_url}membres/cotisations.php?id={$membre.id|escape}">Suivi des cotisations</a></li>
    </ul>
{else}
    {include file="admin/_head.tpl" title="Enregistrer une cotisation" current="membres/cotisations" js=1}

    <ul class="actions">
        <li><a href="{$admin_url}membres/cotisations/">Suivi des cotisations</a></li>
        <li class="current"><a href="{$admin_url}membres/cotisations/ajout.php">Enregistrer une cotisation</a></li>
        <li><a href="{$admin_url}membres/cotisations/rappels.php">État des rappels</a></li>
    </ul>
{/if}

{if $error}
    <p class="error">{$error|escape}</p>
{else if $user.droits.compta >= Garradin\Membres::DROIT_ECRITURE}
    <p class="help">
        Cette page sert à enregistrer les cotisations des membres de l'association.
        Pour enregistrer un don ou une dépense, comme le paiement d'un prestataire ou une facture, il est possible de <a href="{$admin_url}compta/operations/saisir.php">saisir une opération comptable</a>.
    </p>
{/if}

<form method="post" action="{$self_url|escape}">
    <fieldset>
        <legend>Enregistrer une cotisation</legend>
        <dl>
            <dt><label for="f_id_cotisation">Cotisation</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select id="f_id_cotisation" name="id_cotisation">
                    <option value="0" {form_field name="id_cotisation" selected=0}>-- Aucune, paiement non lié</option>
                    {foreach from=$cotisations item="co"}
                    <option value="{$co.id|escape}" {form_field name="id_cotisation" selected=$co.id default=$default_co} data-compta="{$co.id_categorie_compta|escape}">
                        {$co.intitule|escape}
                        — {$co.montant|html_money} {$config.monnaie|escape}
                        — {if $co.duree}pour {$co.duree|escape} jours
                        {elseif $co.debut}
                            du {$co.debut|format_sqlite_date_to_french} au {$co.fin|format_sqlite_date_to_french}
                        {else}
                            ponctuelle
                        {/if}
                    </option>
                    {/foreach}
                </select>
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
            <dt><label for="f_date">Date</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="date" id="f_date" value="{form_field name=date}" /></dd>
            {if !$membre}
            <dt><label for="f_id_membre">Numéro de membre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" name="id_membre" id="f_id_membre" value="{form_field name=id_membre}" step="1" min="1" /></dd>
            {/if}
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="add_cotisation"}
        {if $membre}<input type="hidden" name="id_membre" value="{$membre.id|escape}" />{/if}
        <input type="submit" name="add" value="Enregistrer &rarr;" />
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

    $('#f_id_cotisation').onchange = function () {
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