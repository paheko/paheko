{include file="admin/_head.tpl" title="Modification de l'opération n°`$operation.id`" current="compta/saisie"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

{if is_null($type)}
    <fieldset>
        <legend>Informations sur l'opération</legend>
        <dl>
            <dt><label for="f_date">Date</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="date" id="f_date" value="{form_field name=date default=$operation.date|date_fr:'Y-m-d'}" size="10" /></dd>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle data=$operation}" /></dd>
            <dt><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" size="5" name="montant" id="f_montant" value="{form_field name=montant data=$operation}" min="0.00" /> {$config.monnaie|escape}</dd>
            <dt><label for="f_compte_debit">Compte débité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="compte_debit" data=$operation}
            </dd>
            <dt><label for="f_compte_credit">Compte crédité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="compte_credit" data=$operation}
            </dd>
            <dt><label for="f_numero_piece">Numéro de pièce comptable</label></dt>
            <dd><input type="text" name="numero_piece" id="f_numero_piece" value="{form_field name=numero_piece data=$operation}" /></dd>
            <dt><label for="f_remarques">Remarques</label></dt>
            <dd><textarea name="remarques" id="f_remarques" rows="4" cols="30">{form_field name=remarques data=$operation}</textarea></dd>
        </dl>
    </fieldset>

{else}
    <fieldset>
        <legend>Informations sur l'opération</legend>
        <dl>
            <dt><label for="f_date">Date</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="date" id="f_date" value="{form_field name=date default=$operation.date|date_fr:'Y-m-d'}" size="10" /></dd>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle data=$operation}" /></dd>
            <dt><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" size="5" name="montant" id="f_montant" value="{form_field name=montant data=$operation}" min="0.00" /> {$config.monnaie|escape}</dd>
            <dt><label for="f_moyen_paiement">Moyen de paiement</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="moyen_paiement" id="f_moyen_paiement" onchange="return changeMoyenPaiement(this);">
                {foreach from=$moyens_paiement item="moyen"}
                    <option value="{$moyen.code|escape}"{if $moyen.code == $operation.moyen_paiement} selected="selected"{/if}>{$moyen.nom|escape}</option>
                {/foreach}
                </select>
            </dd>
            <dt class="cheque"><label for="f_numero_cheque">Numéro de chèque</label></dt>
            <dd class="cheque"><input type="text" name="numero_cheque" id="f_numero_cheque" value="{form_field name=numero_cheque data=$operation}" /></dd>
            <dt class="banque"><label for="f_banque">Compte bancaire</label></dt>
            <dd class="banque">
                <select name="banque" id="f_banque">
                {foreach from=$comptes_bancaires item="compte"}
                    <option value="{$compte.id|escape}"{if ($type == Garradin_Compta_Categories::DEPENSES && $compte.id == $operation.compte_credit) || $compte.id == $operation.compte_debit} selected="selected"{/if}>{$compte.libelle|escape} - {$compte.banque|escape}</option>
                {/foreach}
                </select>
            </dd>
            <dt><label for="f_numero_piece">Numéro de pièce comptable</label></dt>
            <dd><input type="text" name="numero_piece" id="f_numero_piece" value="{form_field name=numero_piece data=$operation}" /></dd>
            <dt><label for="f_remarques">Remarques</label></dt>
            <dd><textarea name="remarques" id="f_remarques" rows="4" cols="30">{form_field name=remarques data=$operation}</textarea></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Catégorie</legend>
        <dl class="catList">
        {foreach from=$categories item="cat"}
            <dt>
                <input type="radio" name="id_categorie" value="{$cat.id|escape}" id="f_cat_{$cat.id|escape}" {form_field name="id_categorie" checked=$cat.id data=$operation} />
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

        window.changeMoyenPaiement = function(elm)
        {
            var cheque = document.getElementsByClassName('cheque');
            var cheque_l = cheque.length;

            for (i = 0; i < cheque_l; i++)
            {
                cheque[i].style.display = elm.value == 'CH' ? 'block' : 'none';
            }

            var banque = document.getElementsByClassName('banque');
            var banque_l = banque.length;

            for (i = 0; i < banque_l; i++)
            {
                banque[i].style.display = (elm.value != 'ES') ? 'block' : 'none';
            }
        };

        changeMoyenPaiement(document.getElementById('f_moyen_paiement'));
    } ());
    {/literal}
    </script>
{/if}

    <p class="submit">
        {csrf_field key="compta_modifier_`$operation.id`"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}