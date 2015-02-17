{include file="admin/_head.tpl" title="Ajouter un membre" current="membres/ajouter" js=1}

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
                {html_champ_membre config=$champ name=$nom}
            {/foreach}
        </dl>
    </fieldset>

    <fieldset>
        <legend>Connexion</legend>
        <dl>
            <dt><label for="f_passe">Mot de passe</label>{if $champs.passe.mandatory} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
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
g.script('scripts/password.js').onload = function () {
    initPasswordField('password_suggest', 'f_passe', 'f_repasse');
};
{/literal}
</script>


{include file="admin/_foot.tpl"}