{include file="admin/_head.tpl" title="Ajouter un membre" current="membres/ajouter"}

{form_errors}

<form method="post" action="{$self_url}">
    <!-- This is to avoid chrome autofill, Chrome developers you suck -->
    <input type="text" style="display: none;" name="email" />
    {if $id_field_name != 'email'}<input type="text" style="display: none;" name="{$id_field_name}" />{/if}
    <input type="password" style="display: none;" name="password" />

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
            <dt><label for="f_passe">Mot de passe</label> (minimum {$password_length} caractères) {if $champs.passe.mandatory} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd class="help">
                Astuce : un mot de passe de quatre mots choisis au hasard dans le dictionnaire est plus sûr 
                et plus simple à retenir qu'un mot de passe composé de 10 lettres et chiffres.
            </dd>
            <dd class="help">
                Pas d'idée&nbsp;? Voici une suggestion choisie au hasard :
                <input type="text" readonly="readonly" title="Cliquer pour utiliser cette suggestion comme mot de passe" id="pw_suggest" value="{$passphrase}" autocomplete="off" />
            </dd>
            <dd><input type="password" name="passe" id="f_passe" value="{form_field name=passe}" pattern="{$password_pattern}" autocomplete="new-password" /></dd>
            <dt><label for="f_repasse">Encore le mot de passe</label> (vérification)</dt>
            <dd><input type="password" name="passe_confirmed" id="f_repasse" value="{form_field name=passe_confirmed}" pattern="{$password_pattern}" autocomplete="new-password" /></dd>
        </dl>
    </fieldset>

    {if $session->canAccess('membres', Membres::DROIT_ADMIN)}
    <fieldset>
        <legend>Général</legend>
        <dl>
            <dt><label for="f_cat">Catégorie du membre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="id_categorie" id="f_cat">
                {foreach from=$membres_cats key="id" item="nom"}
                    <option value="{$id}"{if $current_cat == $id} selected="selected"{/if}>{$nom}</option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>
    {/if}

    <p class="submit">
        {csrf_field key="new_member"}
        {button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
    </p>

</form>

<script type="text/javascript">
{literal}
g.script('scripts/password.js', () => {
    initPasswordField('pw_suggest', 'f_passe', 'f_repasse');
});
{/literal}
</script>


{include file="admin/_foot.tpl"}