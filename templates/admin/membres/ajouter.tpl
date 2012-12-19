{include file="admin/_head.tpl" title="Ajouter un membre" current="membres/ajouter"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Informations personnelles</legend>
        <dl>
            <dt><label for="f_nom">Prénom et nom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom" id="f_nom" value="{form_field name=nom}" /></dd>
            <dt><label for="f_email">Adresse E-Mail</label>{if in_array('email', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="email" name="email" id="f_email" value="{form_field name=email}" /></dd>
            <dt><label for="f_telephone">Numéro de téléphone</label>{if in_array('telephone', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="tel" name="telephone" id="f_telephone" value="{form_field name=telephone}" /></dd>
            <dt><label for="f_adresse">Adresse</label> (numéro, rue, etc.){if in_array('adresse', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><textarea name="adresse" id="f_adresse" rows="4" cols="30">{form_field name=adresse}</textarea></dd>
            <dt><label for="f_code_postal">Code postal</label>{if in_array('code_postal', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="number" name="code_postal" id="f_code_postal" value="{form_field name=code_postal}" /></dd>
            <dt><label for="f_ville">Ville</label>{if in_array('ville', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="text" name="ville" id="f_ville" value="{form_field name=ville}" /></dd>
            <dt><label for="f_pays">Pays</label> {if in_array('pays', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd>
                <select name="pays" id="f_pays">
                {foreach from=$pays key="cc" item="nom"}
                    <option value="{$cc|escape}"{if $cc == $current_cc} selected="selected"{/if}>{$nom|escape}</option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Connexion</legend>
        <dl>
            <dt><label for="f_passe">Mot de passe</label>{if in_array('passe', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd class="help">
                Pas d'idée ? Voici une suggestion choisie au hasard :
                <tt title="Cliquer pour utiliser cette suggestion comme mot de passe" onclick="fillPassword(this);">{$passphrase|escape}</tt>
            </dd>
            <dd><input type="password" name="passe" id="f_passe" value="{form_field name=passe}" /></dd>
            <dt><label for="f_repasse">Encore le mot de passe</label> (vérification){if in_array('passe', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="password" name="repasse" id="f_repasse" value="{form_field name=repasse}" /></dd>
        </dl>
    </fieldset>

    {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}
    <fieldset>
        <legend>Général</legend>
        <dl>
            <dt><label for="f_cat">Catégorie du membre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="id_categorie" id="f_cat">
                {foreach from=$membres_cats key="id" item="nom"}
                    <option value="{$id|escape}"{if $current_cat == $id} selected="selected"{/if}>{$nom|escape}</option>
                {/foreach}
                </select>
            </dd>
            <dt>
                <input type="checkbox" id="f_lettre" name="lettre_infos" value="1" {form_field name="lettre_infos" checked="1"} />
                <label for="f_lettre">Inscription à la lettre d'information</label>
            </dt>
        </dl>
    </fieldset>
    {/if}

    <p class="submit">
        {csrf_field key="new_member"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

<script type="text/javascript">
{literal}
function fillPassword(elm)
{
    var pw = elm.textContent || elm.innerText;
    document.getElementById('f_passe').value = pw;
    document.getElementById('f_repasse').value = pw;
}
{/literal}
</script>

{include file="admin/_foot.tpl"}