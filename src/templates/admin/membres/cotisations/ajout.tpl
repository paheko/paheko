{if $membre}
    {include file="admin/_head.tpl" title="Enregistrer une cotisation pour le membre" current="membres/cotisations" js=1}

    <ul class="actions">
        <li><a href="{$admin_url}membres/fiche.php?id={$membre.id}"><b>{$membre.identite}</b></a></li>
        <li><a href="{$admin_url}membres/modifier.php?id={$membre.id}">Modifier</a></li>
        {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN) && $user.id != $membre.id}
            <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id}">Supprimer</a></li>
        {/if}
        <li><a href="{$admin_url}membres/cotisations.php?id={$membre.id}">Suivi des cotisations</a></li>
    </ul>
{else}
    {include file="admin/_head.tpl" title="Enregistrer une cotisation" current="membres/cotisations" js=1}

    <ul class="actions">
        <li><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
        <li class="current"><a href="{$admin_url}membres/cotisations/ajout.php">Saisie d'une cotisation</a></li>
        {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN)}
            <li><a href="{$admin_url}membres/cotisations/gestion/rappels.php">Gestion des rappels automatiques</a></li>
        {/if}
    </ul>
{/if}

{form_errors}

{if $session->canAccess('compta', Garradin\Membres::DROIT_ECRITURE)}
    <p class="help">
        Cette page sert à enregistrer les cotisations des membres de l'association.
        Pour enregistrer un don ou une dépense, comme le paiement d'un prestataire ou une facture, il est possible de <a href="{$admin_url}compta/operations/saisir.php">saisir une opération comptable</a>.
    </p>
{/if}

<form method="post" action="{$self_url}">
    <fieldset>
        <legend>Enregistrer une cotisation</legend>
        <dl>
            <dt><label for="f_id_cotisation">Cotisation</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select id="f_id_cotisation" required="required" name="id_cotisation">
                    {foreach from=$cotisations item="co"}
                    <option value="{$co.id}" {form_field name="id_cotisation" selected=$co.id default=$default_co} data-compta="{$co.id_categorie_compta}" data-amount="{$co.montant}">
                        {$co.intitule}
                        — {$co.montant|escape|html_money} {$config.monnaie}
                        — {if $co.duree}pour {$co.duree} jours
                        {elseif $co.debut}
                            du {$co.debut|format_sqlite_date_to_french} au {$co.fin|format_sqlite_date_to_french}
                        {else}
                            ponctuelle
                        {/if}
                    </option>
                    {/foreach}
                </select>
            </dd>
            <dt><label for="f_date">Date</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="date" id="f_date" value="{form_field name=date default=$default_date}" required="required" /></dd>
            {if !$membre}
            <dt><label for="f_numero_membre">Numéro de membre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" name="numero_membre" id="f_numero_membre" value="{form_field name=numero_membre}" step="1" min="1" required="required" /></dd>
            {/if}
            <dt class="f_compta"><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="f_compta"><input type="number" name="montant" step="0.01" min="0.00" id="f_montant" value="{form_field name=montant default=$default_amount}" /></dd>
            <dt class="f_compta"><label for="f_moyen_paiement">Moyen de paiement</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="f_compta">
                <select name="moyen_paiement" id="f_moyen_paiement">
                {foreach from=$moyens_paiement item="moyen"}
                    <option value="{$moyen.code}"{if $moyen.code == $moyen_paiement} selected="selected"{/if}>{$moyen.nom}</option>
                {/foreach}
                </select>
            </dd>
            <dd class="f_compta f_a_encaisser">
                <input type="checkbox" name="a_encaisser" value="1" id="f_a_encaisser" {form_field name=a_encaisser checked="1"} />
                <label for="f_a_encaisser">En attente d'encaissement</label>
            </dd>
            <dt class="f_compta f_cheque"><label for="f_numero_cheque">Numéro de chèque</label></dt>
            <dd class="f_compta f_cheque"><input type="text" name="numero_cheque" id="f_numero_cheque" value="{form_field name=numero_cheque}" /></dd>
            <dt class="f_compta f_banque"><label for="f_banque">Compte bancaire</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="f_compta f_banque">
                <select name="banque" id="f_banque">
                {foreach from=$comptes_bancaires item="compte"}
                    <option value="{$compte.id}"{if $compte.id == $banque} selected="selected"{/if}>{$compte.libelle} - {$compte.banque}</option>
                {/foreach}
                </select>
            </dd>
            <dt class="f_compta"><label for="f_numero_piece">Numéro de pièce comptable</label></dt>
            <dd class="f_compta"><input type="text" name="numero_piece" id="f_numero_piece" value="{form_field name=numero_piece}" /></dd>
            <dt class="f_compta"><label for="f_remarques">Remarques</label></dt>
            <dd class="f_compta"><textarea name="remarques" id="f_remarques" rows="4" cols="30">{form_field name=remarques}</textarea></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="add_cotisation"}
        {if $membre}<input type="hidden" name="id_membre" value="{$membre.id}" />{/if}
        <input type="submit" name="add" value="Enregistrer &rarr;" />
    </p>
</form>

<script type="text/javascript">
{literal}
(function () {
    window.changeMoyenPaiement = function()
    {
        var elm = $('#f_moyen_paiement');
        g.toggle('.f_cheque', elm.value == 'CH');
        g.toggle('.f_banque', elm.value != 'ES');

        g.toggle('.f_a_encaisser', elm.value == 'CB' || elm.value == 'CH');
        cocherAEncaisser();
    };

    function cocherAEncaisser()
    {
        var elm = $('#f_a_encaisser');
        g.toggle('.f_banque', !elm.checked && $('#f_moyen_paiement').value != 'ES');
    }

    changeMoyenPaiement();
    cocherAEncaisser();

    $('#f_moyen_paiement').onchange = changeMoyenPaiement;
    $('#f_a_encaisser').onchange = cocherAEncaisser;

    $('#f_id_cotisation').onchange = function () {
        if (this.options[this.selectedIndex].getAttribute('data-compta'))
        {
            $('#f_montant').value = this.options[this.selectedIndex].getAttribute('data-amount'); 
            g.toggle('.f_compta', true);
            changeMoyenPaiement();
            cocherAEncaisser();
        }
        else
        {
            g.toggle('.f_compta', false);
        }
    };

    if (!$('#f_id_cotisation').options[$('#f_id_cotisation').selectedIndex].getAttribute('data-compta'))
    {
        g.toggle('.f_compta', false);
    }
} ());
{/literal}
</script>

{include file="admin/_foot.tpl"}