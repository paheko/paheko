{include file="admin/_head.tpl" title="Saisie d'une opération" current="compta/saisie" js=1}

<form method="post" action="{$self_url}">
    {form_errors}

    {if $ok}
        <p class="confirm">
            L'opération numéro <a href="{$admin_url}compta/operations/voir.php?id={$ok}">{$ok}</a> a été ajoutée.
            (<a href="{$admin_url}compta/operations/voir.php?id={$ok}">Voir l'opération</a>)
        </p>
    {/if}

    <fieldset>
        <legend>Type d'écriture</legend>
        <dl>
            {input type="radio" name="type" value="recette" label="Recette"}
            {input type="radio" name="type" value="depense" label="Dépense"}
            {input type="radio" name="type" value="virement" label="Virement" help="Faire un virement entre comptes, déposer des espèces en banque, etc."}
            {input type="radio" name="type" value="dette" label="Dette" help="Quand l'association doit de l'argent à un membre ou un fournisseur"}
            {input type="radio" name="type" value="creance" label="Créance" help="Quand un membre ou un fournisseur doit de l'argent à l'association"}
            {input type="radio" name="type" value="avance" label="Saisie avancée" help="Choisir les comptes du plan comptable, ventiler une écriture sur plusieurs comptes, etc."}
        </dl>
    </fieldset>

    <fieldset>
        <legend>Informations</legend>
        <dl>
            {input type="date" name="date" value=$date label="Date" required}
            {input type="text" name="libelle" label="Libellé" required}
            {input type="number" name="montant" label="Montant" min="0.00" step="0.01" value="0.00" required} {$config.monnaie}
        </dl>
        <dl class="type_recette type_depense">
            {input type="select" name="moyen" label="Moyen de paiement" required options=$moyens_paiement}
            {input type="select" name="compte" options=$comptes_encaissement label="Compte d'encaissement" required}
            {input type="text" name="reference_paiement" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc."}
        </dl>
        <dl class="type_avance">
            {input type="compta_lignes" name="lignes" label="Lignes de l'écriture"}
        </dl>
    </fieldset>

    <fieldset class="type_virement">
        <legend>Virement</legend>
        <dl>
            {input type="select" name="from" options=$comptes label="De" required}
            {input type="select" name="to" options=$comptes label="Vers" required}
        </dl>
    </fieldset>

    <fieldset>
        <legend>Détails</legend>
        <dl>
            {input type="datalist" name="membre" label="Membres associés"}
            {input type="text" name="numero_piece" label="Numéro de pièce comptable"}
            {input type="textarea" name="remarques" label="Remarques" rows=4 cols=30}

            {if count($projets) > 0}
                {input type="select" name="projet" options=$projets}
            {/if}
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
            {input type="radio" name="categorie_recette" value=$cat.id label=$cat.intitule}
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
            {input type="radio" name="categorie_depense" value=$cat.id label=$cat.intitule}
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

        $('#f_date').focus();
    } ());
    {/literal}
    </script>

    <p class="submit">
        {csrf_field key="compta_saisie"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}