{include file="admin/_head.tpl" title="Mes informations personnelles" current="mes_infos"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Informations personnelles</legend>
        <dl>
        {if in_array('nom', $config.champs_modifiables_membre)}
            <dt><label for="f_nom">Prénom et bom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom" id="f_nom" value="{form_field data=$membre name=nom}" /></dd>
        {else}
            <dt>Prénom et nom <b>(non modifiable)</b></dt>
            <dd>{$membre.nom|escape}</dd>
        {/if}
        {if in_array('email', $config.champs_modifiables_membre)}
            <dt><label for="f_email">Adresse E-Mail</label>{if in_array('email', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="email" name="email" id="f_email" value="{form_field data=$membre name=email}" /></dd>
        {else}
            <dt>E-Mail <b>(non modifiable)</b></dt>
            <dd>{$membre.email|escape}</dd>
        {/if}
        {if in_array('telephone', $config.champs_modifiables_membre)}
            <dt><label for="f_telephone">Numéro de téléphone</label>{if in_array('telephone', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="tel" name="telephone" id="f_telephone" value="{form_field data=$membre name=telephone}" /></dd>
        {else}
            <dt>Numéro de téléphone <b>(non modifiable)</b></dt>
            <dd>{$membre.telephone|escape}</dd>
        {/if}
        {if in_array('adresse', $config.champs_modifiables_membre)}
            <dt><label for="f_adresse">Adresse</label> (numéro, rue, etc.){if in_array('adresse', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><textarea name="adresse" id="f_adresse" rows="4" cols="30">{form_field data=$membre name=adresse}</textarea></dd>
        {else}
            <dt>Adresse <b>(non modifiable)</b></dt>
            <dd>{$membre.adresse|escape|nl2br}</dd>
        {/if}
        {if in_array('code_postal', $config.champs_modifiables_membre)}
            <dt><label for="f_code_postal">Code postal</label>{if in_array('code_postal', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="number" name="code_postal" id="f_code_postal" value="{form_field data=$membre name=code_postal}" /></dd>
        {else}
            <dt>Code postal <b>(non modifiable)</b></dt>
            <dd>{$membre.code_postal|escape}</dd>
        {/if}
        {if in_array('ville', $config.champs_modifiables_membre)}
            <dt><label for="f_ville">Ville</label>{if in_array('ville', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="text" name="ville" id="f_ville" value="{form_field data=$membre name=ville}" /></dd>
        {else}
            <dt>Ville <b>(non modifiable)</b></dt>
            <dd>{if $membre.ville}{$membre.ville|escape}{else}(vide){/if}</dd>
        {/if}
        {if in_array('pays', $config.champs_modifiables_membre)}
            <dt><label for="f_pays">Pays</label> {if in_array('pays', $obligatoires)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd>
                <select name="pays" id="f_pays">
                {foreach from=$pays key="cc" item="nom"}
                    <option value="{$cc|escape}"{if $cc == $current_cc} selected="selected"{/if}>{$nom|escape}</option>
                {/foreach}
                </select>
            </dd>
        {else}
            <dt>Pays<b>(non modifiable)</b></dt>
            <dd>{if $membre.pays}{$membre.pays|get_country_name}{else}(vide){/if}</dd>
        {/if}
        </dl>
    </fieldset>

    {if in_array('passe', $config.champs_modifiables_membre)}
    <fieldset>
        <legend>Changer mon mot de passe</legend>
        <dl>
            <dd>Vous avez déjà un mot de passe, ne remplissez pas les champs suivants si vous ne souhaitez pas les changer.</dd>
            <dt><label for="f_passe">Nouveau mot de passe</label></dt>
            <dd class="help">
                Pas d'idée ? Voici une suggestion choisie au hasard :
                <tt title="Cliquer pour utiliser cette suggestion comme mot de passe" onclick="fillPassword(this);">{$passphrase|escape}</tt>
            </dd>
            <dd><input type="password" name="passe" id="f_passe" value="{form_field name=passe}" /></dd>
            <dt><label for="f_repasse">Encore le mot de passe</label> (vérification)</dt>
            <dd><input type="password" name="repasse" id="f_repasse" value="{form_field name=repasse}" /></dd>
        </dl>
    </fieldset>
    {/if}

    <p class="submit">
        {csrf_field key="edit_me"}
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