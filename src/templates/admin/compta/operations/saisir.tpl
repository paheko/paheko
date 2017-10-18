{include file="admin/_head.tpl" title="Saisie d'une opération" current="compta/saisie" js=1}

<form method="post" action="{$self_url}">
    <ul class="actions">
        <li><input type="radio" name="type" value="recette" {form_field name=type checked=recette default=recette} id="f_type_recette" /><label for="f_type_recette">Recette</label></li>
        <li><input type="radio" name="type" value="depense" {form_field name=type checked=depense} id="f_type_depense" /><label for="f_type_depense">Dépense</label></li>
        <li><input type="radio" name="type" value="virement" {form_field name=type checked=virement} id="f_type_virement" /><label for="f_type_virement">Virement interne</label></li>
        <li><input type="radio" name="type" value="avance" {form_field name=type checked=avance} id="f_type_avance" /><label for="f_type_avance">Saisie avancée</label></li>
    </ul>

    {form_errors}

    {if $ok}
        <p class="confirm">
            L'opération numéro <a href="{$www_url}admin/compta/operations/voir.php?id={$ok}">{$ok}</a> a été ajoutée.
            (<a href="{$www_url}admin/compta/operations/voir.php?id={$ok}">Voir l'opération</a>)
        </p>
    {/if}

    <fieldset>
        <legend>Informations sur l'opération</legend>
        <dl>
            <dt><label for="f_date">Date</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="date" id="f_date" value="{form_field name=date default=$date}" size="10" required="required" /></dd>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle}" required="required" /></dd>
            <dt><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" size="5" name="montant" id="f_montant" value="{form_field name=montant default=0.00}" min="0.00" step="0.01" required="required" /> {$config.monnaie}</dd>
            <dt><label for="f_numero_piece">Numéro de pièce comptable</label></dt>
            <dd><input type="text" name="numero_piece" id="f_numero_piece" value="{form_field name=numero_piece}" /></dd>
            <dt><label for="f_remarques">Remarques</label></dt>
            <dd><textarea name="remarques" id="f_remarques" rows="4" cols="30">{form_field name=remarques}</textarea></dd>
            {if count($projets) > 0}
            <dt><label for="f_projet">Projet</label></dt>
            <dd>
                <select name="projet" id="f_projet">
                    <option value="0">-- Aucun</option>
                    {foreach from=$projets key="id" item="libelle"}
                    <option value="{$id}"{form_field name="projet" selected=$id}>{$libelle}</option>
                    {/foreach}
                </select>
            </dd>
            {/if}
        </dl>
        <dl class="type_recette type_depense">
            <dt><label for="f_moyen_paiement">Moyen de paiement</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="moyen_paiement" id="f_moyen_paiement" required="required">
                {foreach from=$moyens_paiement item="moyen"}
                    <option value="{$moyen.code}"{if $moyen.code == $moyen_paiement} selected="selected"{/if}>{$moyen.nom}</option>
                {/foreach}
                </select>
            </dd>
            <dd class="f_a_encaisser">
                <input type="checkbox" name="a_encaisser" value="1" id="f_a_encaisser" {form_field name=a_encaisser checked="1"} />
                <label for="f_a_encaisser">En attente d'encaissement</label>
            </dd>
            <dt class="f_cheque"><label for="f_numero_cheque">Numéro de chèque</label></dt>
            <dd class="f_cheque"><input type="text" name="numero_cheque" id="f_numero_cheque" value="{form_field name=numero_cheque}" /></dd>
            <dt class="f_banque"><label for="f_banque">Compte bancaire</label></dt>
            <dd class="f_banque">
                <select name="banque" id="f_banque">
                {foreach from=$comptes_bancaires item="compte"}
                    <option value="{$compte.id}"{if $compte.id == $banque} selected="selected"{/if}>{$compte.libelle} - {$compte.banque}</option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <fieldset class="type_avance">
        <legend>Saisie avancée</legend>
        <dl>
            <dt><label for="f_compte_debit">Compte débité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="compte_debit"}
            </dd>
            <dt><label for="f_compte_credit">Compte crédité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="compte_credit"}
            </dd>
        </dl>
    </fieldset>

    <fieldset class="type_virement">
        <legend>Virement</legend>
        <dl>
            <dt><label for="f_compte2">De</label></dt>
            <dd>
                <select name="compte2" id="f_compte2">
                    <option value="{$id_caisse}">Caisse</option>
                {foreach from=$comptes_bancaires item="compte"}
                    <option value="{$compte.id}"{if $compte.id == $banque} selected="selected"{/if}>{$compte.libelle} - {$compte.banque}</option>
                {/foreach}
                    <option value="{$compte_cheque_e_encaisser}">Chèques à encaisser</option>
                    <option value="{$compte_carte_e_encaisser}">Paiement CB à encaisser</option>
                </select>
            </dd>
            <dt><label for="f_compte1">Vers</label></dt>
            <dd>
                <select name="compte1" id="f_compte1">
                    <option value="{$id_caisse}">Caisse</option>
                {foreach from=$comptes_bancaires item="compte"}
                    <option value="{$compte.id}"{if $compte.id == $banque} selected="selected"{/if}>{$compte.libelle} - {$compte.banque}</option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <fieldset class="type_dette">
        <legend>Dette</legend>
        <dl>
            <dt><label for="f_compte_usager">Type de dette</label></dt>
            <dd>
                <input type="radio" name="compte" id="f_compte_usager" value="4110" {form_field name=compte checked=4110 default=4110} />
                <label for="f_compte_usager">Dette envers un membre ou usager</label>
            </dd>
            <dd>
                <input type="radio" name="compte" id="f_compte_fournisseur" value="4010" {form_field name=compte checked=4010} />
                <label for="f_compte_fournisseur">Dette envers un fournisseur</label>
            </dd>
        </dl>
    </fieldset>

    <fieldset class="type_recette">
        <legend>Catégorie</legend>
        <dl class="catList">
        {foreach from=$categories_recettes item="cat"}
            <dt>
                <input type="radio" name="categorie_recette" value="{$cat.id}" id="f_cat_{$cat.id}" {form_field name="categorie" checked=$cat.id} />
                <label for="f_cat_{$cat.id}">{$cat.intitule}</label>
            </dt>
            {if !empty($cat.description)}
                <dd class="desc">{$cat.description}</dd>
            {/if}
        {/foreach}
        </dl>
    </fieldset>

    <fieldset class="type_depense type_dette">
        <legend>Catégorie</legend>
        <dl class="catList">
        {foreach from=$categories_depenses item="cat"}
            <dt>
                <input type="radio" name="categorie_depense" value="{$cat.id}" id="f_cat_{$cat.id}" {form_field name="categorie" checked=$cat.id} />
                <label for="f_cat_{$cat.id}">{$cat.intitule}</label>
            </dt>
            {if !empty($cat.description)}
                <dd class="desc">{$cat.description}</dd>
            {/if}
        {/foreach}
        </dl>
    </fieldset>


    <script type="text/javascript">
    {literal}
    (function () {

        function changeMoyenPaiement()
        {
            var elm = $('#f_moyen_paiement');
            g.toggle('.f_cheque', elm.value == 'CH');
            g.toggle('.f_banque', elm.value != 'ES');

            g.toggle('.f_a_encaisser', elm.value == 'CB' || elm.value == 'CH');

            cocherAEncaisser();
        }

        function changeTypeSaisie(type)
        {
            g.toggle(['.type_dette', '.type_recette', '.type_depense', '.type_avance', '.type_virement'], false);
            g.toggle('.type_' + type, true);
        }

        function cocherAEncaisser()
        {
            var elm = $('#f_a_encaisser');
            g.toggle('.f_banque', !elm.checked && $('#f_moyen_paiement').value != 'ES');
        }

        changeMoyenPaiement();
        changeTypeSaisie(document.forms[0].type.value);
        cocherAEncaisser();

        $('#f_moyen_paiement').onchange = changeMoyenPaiement;

        $('#f_a_encaisser').onchange = cocherAEncaisser;

        var inputs = $('input[name="type"]');

        for (var i = 0; i < inputs.length; i++)
        {
            inputs[i].onchange = function (e) {
                changeTypeSaisie(this.value);
            };
        }
    } ());
    {/literal}
    </script>

    <p class="submit">
        {csrf_field key="compta_saisie"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}