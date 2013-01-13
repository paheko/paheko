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
            {foreach from=$champs item="champ" key="nom"}
            {if empty($champ.private) && $nom != 'passe'}
                {html_champ_membre config=$champ name=$nom data=$membre}
            {/if}
            {/foreach}
        </dl>
    </fieldset>

    {if array_key_exists('passe', $champs)}
    <fieldset>
        <legend>Changer mon mot de passe</legend>
        {if empty($champs.passe.editable)}
            <p class="help">Vous devez contacter un administrateur pour changer votre mot de passe.</p>
        {else}
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
        {/if}
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