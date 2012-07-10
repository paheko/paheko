{include file="admin/_head.tpl" title="Saisie d'une opération" current="compta/saisie" custom_js="datepickr.js"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<ul class="actions">
    <li><a href="?recettes">Recettes</a></li>
    <li><a href="?depenses">Dépenses</a></li>
    <li><a href="?autres">Autres</a></li>
    <li class="current"><a href="?avance">Saisie avancée</a></li>
</ul>

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Informations sur l'opération</legend>
        <dl>
            <dt><label for="f_date">Date</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="date" id="f_date" value="{form_field name=date}" size="10" /></dd>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle}" /></dd>
            <dt><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" size="5" name="montant" id="f_montant" value="{form_field name=montant default=0.00}" min="0.00" /> €</dd>
            <dt><label for="f_compte_debit">Compte débité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="compte_debit"}
            </dd>
            <dt><label for="f_compte_credit">Compte crédité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="compte_credit"}
            </dd>
            <dt><label for="f_numero_piece">Numéro de pièce comptable</label></dt>
            <dd><input type="text" name="numero_piece" id="f_numero_piece" value="{form_field name=numero_piece}" /></dd>
            <dt><label for="f_remarques">Remarques</label></dt>
            <dd><textarea name="remarques" id="f_remarques" rows="4" cols="30">{form_field name=remarques}</textarea></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_saisie"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}