{include file="admin/_head.tpl" title="Saisie d'une opération" current="compta/saisie" js=1}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

{if $ok}
    <p class="confirm">
        L'opération numéro <a href="{$www_url}admin/compta/operations/voir.php?id={$ok|escape}">{$ok|escape}</a> a été ajoutée.
        (<a href="{$www_url}admin/compta/operations/voir.php?id={$ok|escape}">Voir l'opération</a>)
    </p>
{/if}

<ul class="actions">
    <li{if $type == Garradin\Compta\Categories::RECETTES} class="current"{/if}><a href="{$www_url}admin/compta/operations/saisir.php?recette">Recette</a></li>
    <li{if $type == Garradin\Compta\Categories::DEPENSES} class="current"{/if}><a href="{$www_url}admin/compta/operations/saisir.php?depense">Dépense</a></li>
    <li{if $type === 'virement'} class="current"{/if}><a href="{$www_url}admin/compta/operations/saisir.php?virement">Virement interne</a></li>
    <li{if $type === 'dette'} class="current"{/if}><a href="{$www_url}admin/compta/operations/saisir.php?dette">Dette</a></li>
    <li{if is_null($type)} class="current"{/if}><a href="{$www_url}admin/compta/operations/saisir.php?avance">Saisie avancée</a></li>
</ul>

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Informations sur l'opération</legend>
        <dl>
            <dt><label for="f_date">Date</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="date" id="f_date" value="{form_field name=date default=$date}" size="10" required="required" /></dd>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle}" required="required" /></dd>
            <dt><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" size="5" name="montant" id="f_montant" value="{form_field name=montant default=0.00}" min="0.00" step="0.01" required="required" /> {$config.monnaie|escape}</dd>

{if is_null($type)}
            <dt><label for="f_compte_debit">Compte débité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="compte_debit"}
            </dd>
            <dt><label for="f_compte_credit">Compte crédité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="compte_credit"}
            </dd>
{elseif $type === 'virement'}
            <dt><label for="f_compte1">Compte débité</label></dt>
            <dd>
                <select name="compte1" id="f_compte1">
                    <option value="{Garradin\Compta\Comptes::CAISSE}">Caisse</option>
                {foreach from=$comptes_bancaires item="compte"}
                    <option value="{$compte.id|escape}"{if $compte.id == $banque} selected="selected"{/if}>{$compte.libelle|escape} - {$compte.banque|escape}</option>
                {/foreach}
                </select>
            </dd>
            <dt><label for="f_compte2">Compte crédité</label></dt>
            <dd>
                <select name="compte2" id="f_compte2">
                    <option value="{Garradin\Compta\Comptes::CAISSE}">Caisse</option>
                {foreach from=$comptes_bancaires item="compte"}
                    <option value="{$compte.id|escape}"{if $compte.id == $banque} selected="selected"{/if}>{$compte.libelle|escape} - {$compte.banque|escape}</option>
                {/foreach}
                </select>
            </dd>
{elseif $type === 'dette'}
            <dt><label for="f_compte_usager">Type de dette</label></dt>
            <dd>
                <input type="radio" name="compte" id="f_compte_usager" value="4110" {form_field name=compte checked=4110 default=4110} />
                <label for="f_compte_usager">Dette envers un membre ou usager</label>
            </dd>
            <dd>
                <input type="radio" name="compte" id="f_compte_fournisseur" value="4010" {form_field name=compte checked=4010} />
                <label for="f_compte_fournisseur">Dette envers un fournisseur</label>
            </dd>
{else}
            <dt><label for="f_moyen_paiement">Moyen de paiement</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="moyen_paiement" id="f_moyen_paiement" required="required">
                {foreach from=$moyens_paiement item="moyen"}
                    <option value="{$moyen.code|escape}"{if $moyen.code == $moyen_paiement} selected="selected"{/if}>{$moyen.nom|escape}</option>
                {/foreach}
                </select>
            </dd>
            <dt class="f_cheque"><label for="f_numero_cheque">Numéro de chèque</label></dt>
            <dd class="f_cheque"><input type="text" name="numero_cheque" id="f_numero_cheque" value="{form_field name=numero_cheque}" /></dd>
            <dt class="f_banque"><label for="f_banque">Compte bancaire</label></dt>
            <dd class="f_banque">
                <select name="banque" id="f_banque">
                {foreach from=$comptes_bancaires item="compte"}
                    <option value="{$compte.id|escape}"{if $compte.id == $banque} selected="selected"{/if}>{$compte.libelle|escape} - {$compte.banque|escape}</option>
                {/foreach}
                </select>
            </dd>
{/if}
            <dt><label for="f_numero_piece">Numéro de pièce comptable</label></dt>
            <dd><input type="text" name="numero_piece" id="f_numero_piece" value="{form_field name=numero_piece}" /></dd>
            <dt><label for="f_remarques">Remarques</label></dt>
            <dd><textarea name="remarques" id="f_remarques" rows="4" cols="30">{form_field name=remarques}</textarea></dd>
        </dl>
    </fieldset>

{if $type == Garradin\Compta\Categories::DEPENSES || $type == Garradin\Compta\Categories::RECETTES || $type == 'dette'}
    <fieldset>
        <legend>Catégorie</legend>
        <dl class="catList">
        {foreach from=$categories item="cat"}
            <dt>
                <input type="radio" name="categorie" value="{$cat.id|escape}" id="f_cat_{$cat.id|escape}" {form_field name="categorie" checked=$cat.id} />
                <label for="f_cat_{$cat.id|escape}">{$cat.intitule|escape}</label>
            </dt>
            {if !empty($cat.description)}
                <dd class="desc">{$cat.description|escape}</dd>
            {/if}
        {/foreach}
        </dl>
    </fieldset>

    <script type="text/javascript">
    {literal}
    (function () {

        window.changeMoyenPaiement = function()
        {
            var elm = $('#f_moyen_paiement');
            g.toggle('.f_cheque', elm.value == 'CH');
            g.toggle('.f_banque', elm.value != 'ES');
        };

        changeMoyenPaiement();

        $('#f_moyen_paiement').onchange = changeMoyenPaiement;
    } ());
    {/literal}
    </script>
{/if}

    <p class="submit">
        {csrf_field key="compta_saisie"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}