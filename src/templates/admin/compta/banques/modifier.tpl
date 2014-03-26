{include file="admin/_head.tpl" title="Modifier un compte" current="compta/banques"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Modifier un compte bancaire</legend>
        <dl>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle data=$compte}" required="required" /></dd>
            <dt><label for="f_banque">Nom de la banque</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="banque" id="f_banque" value="{form_field name=banque data=$compte}" required="required" /></dd>
            <dt><label for="f_iban">Numéro IBAN</label></dt>
            <dd><input type="text" size="30" name="iban" id="f_iban" value="{form_field name=iban data=$compte}" /></dd>
            <dt><label for="f_bic">Code BIC/SWIFT de la banque</label></dt>
            <dd><input type="text" size="10" name="bic" id="f_bic" value="{form_field name=bic data=$compte}" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_edit_banque_`$compte.id`"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}