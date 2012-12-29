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
            {foreach from=$champs item="champ" key="nom"}
            {if empty($champ.private) || $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
                {html_champ_membre config=$champ name=$nom}
            {/if}
            {/foreach}
        </dl>
    </fieldset>

    <fieldset>
        <legend>Connexion</legend>
        <dl>
            <dt><label for="f_passe">Mot de passe</label></dt>
            <dd class="help">
                Pas d'idée ? Voici une suggestion choisie au hasard :
                <tt title="Cliquer pour utiliser cette suggestion comme mot de passe" onclick="fillPassword(this);">{$passphrase|escape}</tt>
            </dd>
            <dd><input type="password" name="passe" id="f_passe" value="{form_field name=passe}" /></dd>
            <dt><label for="f_repasse">Encore le mot de passe</label> (vérification)</dt>
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