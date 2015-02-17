{include file="admin/_head.tpl" title="Mes informations personnelles" current="mes_infos" js=1}

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
                {html_champ_membre config=$champ name=$nom data=$membre user_mode=true}
            {/if}
            {/foreach}
        </dl>
    </fieldset>

    <fieldset>
        <legend>Changer mon mot de passe</legend>
        {if $user.droits.membres < Garradin\Membres::DROIT_ADMIN && (!empty($champs.passe.private) || empty($champs.passe.editable))}
            <p class="help">Vous devez contacter un administrateur pour changer votre mot de passe.</p>
        {else}
            <dl>
                <dd>Vous avez déjà un mot de passe, ne remplissez les champs suivants que si vous souhaitez en changer.</dd>
                <dt><label for="f_passe">Nouveau mot de passe</label></dt>
                <dd class="help">
                    Astuce : un mot de passe de quatre mots choisis au hasard dans le dictionnaire est plus sûr 
                    et plus simple à retenir qu'un mot de passe composé de 10 lettres et chiffres.
                </dd>
                <dd class="help">
                    Pas d'idée&nbsp;? Voici une suggestion choisie au hasard :
                    <input type="text" readonly="readonly" title="Cliquer pour utiliser cette suggestion comme mot de passe" id="password_suggest" value="{$passphrase|escape}" />
                </dd>
                <dd><input type="password" name="passe" id="f_passe" value="{form_field name=passe}" pattern=".{ldelim}5,{rdelim}" /></dd>
                <dt><label for="f_repasse">Encore le mot de passe</label> (vérification)</dt>
                <dd><input type="password" name="repasse" id="f_repasse" value="{form_field name=repasse}" pattern=".{ldelim}5,{rdelim}" /></dd>
            </dl>
        {/if}
    </fieldset>

    <p class="submit">
        {csrf_field key="edit_me"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>


<script type="text/javascript">
{literal}
g.script('scripts/password.js').onload = function () {
    initPasswordField('password_suggest', 'f_passe', 'f_repasse');
};
{/literal}
</script>

{include file="admin/_foot.tpl"}