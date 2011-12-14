{include file="admin/_head.tpl" title="Configuration" current="config"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Informations sur l'association</legend>
        <dl>
            <dt><label for="f_nom_asso">Nom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom_asso" id="f_nom_asso" value="{form_field data=$config name=nom_asso}" /></dd>
            <dt><label for="f_email_asso">Adresse E-Mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="email" name="email_asso" id="f_email_asso" value="{form_field data=$config name=email_asso}" /></dd>
            <dt><label for="f_adresse_asso">Adresse postale</label></dt>
            <dd><textarea cols="50" rows="5" name="adresse_asso" id="f_adresse_asso">{form_field data=$config name=adresse_asso}</textarea></dd>
            <dt><label for="f_site_asso">Site web</label></dt>
            <dd><input type="url" name="site_asso" id="f_site_asso" value="{form_field name=site_asso data=$config}" /></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Envois par E-Mail</legend>
        <dl>
            <dt><label for="f_email_envoi_automatique">Adresse E-Mail expéditeur des messages automatiques</label></dt>
            <dd><input type="text" name="email_envoi_automatique" id="f_email_envoi_automatique" value="{form_field data=$config name=email_envoi_automatique}" /></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Champs des données membres</legend>
        <dl>
            <dt><label for="f_champs_obligatoires_nom">Champs obligatoires</label></dt>
            <dd>
            {foreach from=$champs_membres key="champ" item="nom"}
                <input type="checkbox" name="champs_obligatoires[]"
                    id="f_champs_obligatoires_{$champ|escape}"
                    value="{$champ|escape}"
                    {if $champ == 'nom'}checked="checked" disabled="disabled"
                    {elseif in_array($champ, $config.champs_obligatoires)}checked="checked"
                    {/if}
                    />
                <label for="f_champs_obligatoires_{$champ|escape}">{$nom|escape}</label>
                {if $champ == 'nom'}<small>(non désactivable)</small>{/if}
                <br />
            {/foreach}
            </dd>
            <dt><label for="f_champs_modifiables_membre_nom">Champs modifiables par le membre</label></dt>
            <dd>
            {foreach from=$champs_membres key="champ" item="nom"}
                <input type="checkbox" name="champs_modifiables_membre[]"
                    id="f_champs_modifiables_membre_{$champ|escape}"
                    value="{$champ|escape}"
                    {if in_array($champ, $config.champs_modifiables_membre)}checked="checked"
                    {/if}
                    />
                <label for="f_champs_modifiables_membre_{$champ|escape}">{$nom|escape}</label><br />
            {/foreach}
            </dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Catégories par défaut</legend>
        <dl>
            <dt><label for="f_categorie_membres">Catégorie par défaut des nouveaux membres</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="categorie_membres" id="f_categorie_membres">
                {foreach from=$membres_cats key="id" item="nom"}
                    <option value="{$id|escape}"{if $config.categorie_membres == $id} selected="selected"{/if}>{$nom|escape}</option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="config"}
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