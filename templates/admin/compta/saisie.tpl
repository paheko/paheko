{include file="admin/_head.tpl" title="Saisie d'une opération" current="compta/saisie"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<ul class="actions">
    <li{if $type == Garradin_Compta_Categories::RECETTES} class="current"{/if}><a href="{$www_url}admin/compta/saisie.php?recette">Recette</a></li>
    <li{if $type == Garradin_Compta_Categories::DEPENSES} class="current"{/if}><a href="{$www_url}admin/compta/saisie.php?depense">Dépense</a></li>
    <li{if $type === 'virement'} class="current"{/if}><a href="{$www_url}admin/compta/saisie.php?virement">Virement interne</a></li>
    <li{if $type === 'dette'} class="current"{/if}><a href="{$www_url}admin/compta/saisie.php?dette">Dette</a></li>
    <li{if is_null($type)} class="current"{/if}><a href="{$www_url}admin/compta/saisie.php?avance">Saisie avancée</a></li>
</ul>

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Informations sur l'opération</legend>
        <dl>
            <dt><label for="f_date">Date</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="date" id="f_date" value="{form_field name=date default=$date}" size="10" /></dd>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle}" /></dd>
            <dt><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" size="5" name="montant" id="f_montant" value="{form_field name=montant default=0.00}" min="0.00" /> {$config.monnaie|escape}</dd>

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
                    <option value="{Garradin_Compta_Comptes::CAISSE}">Caisse</option>
                {foreach from=$comptes_bancaires item="compte"}
                    <option value="{$compte.id|escape}"{if $compte.id == $banque} selected="selected"{/if}>{$compte.libelle|escape} - {$compte.banque|escape}</option>
                {/foreach}
                </select>
            </dd>
            <dt><label for="f_compte2">Compte crédité</label></dt>
            <dd>
                <select name="compte2" id="f_compte2">
                    <option value="{Garradin_Compta_Comptes::CAISSE}">Caisse</option>
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
                <select name="moyen_paiement" id="f_moyen_paiement" onchange="return changeMoyenPaiement(this);">
                {foreach from=$moyens_paiement item="moyen"}
                    <option value="{$moyen.code|escape}"{if $moyen.code == $moyen_paiement} selected="selected"{/if}>{$moyen.nom|escape}</option>
                {/foreach}
                </select>
            </dd>
            <dt class="cheque"><label for="f_numero_cheque">Numéro de chèque</label></dt>
            <dd class="cheque"><input type="text" name="numero_cheque" id="f_numero_cheque" value="{form_field name=numero_cheque}" /></dd>
            <dt class="banque"><label for="f_banque">Compte bancaire</label></dt>
            <dd class="banque">
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

{if $type == Garradin_Compta_Categories::DEPENSES || $type == Garradin_Compta_Categories::RECETTES || $type == 'dette'}
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
        {csrf_field key="compta_saisie"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}