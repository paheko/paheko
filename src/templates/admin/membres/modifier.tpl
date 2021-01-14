{include file="admin/_head.tpl" title="Modifier un membre" current="membres"}

<nav class="tabs">
    <ul>
        <li><a href="{$admin_url}membres/fiche.php?id={$membre.id}">{$membre.identite}</a></li>
        <li class="current"><a href="{$admin_url}membres/modifier.php?id={$membre.id}">Modifier</a></li>
        {if $session->canAccess('membres', Membres::DROIT_ADMIN) && $user.id != $membre.id}
            <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id}">Supprimer</a></li>
        {/if}
    </ul>
</nav>

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
                {html_champ_membre config=$champ name=$nom data=$membre}
            {/foreach}
        </dl>
    </fieldset>

    <fieldset>
        <legend>{if $membre.passe}Changer le mot de passe{else}Choisir un mot de passe{/if}</legend>
        <dl>
        {if $membre.passe}
            <dd>Ce membre a déjà un mot de passe, mais vous pouvez le changer si besoin.</dd>
        {else}
            <dd>Ce membre n'a pas encore de mot de passe et ne peut donc se connecter.</dd>
        {/if}
            <dt><label for="f_passe">Nouveau mot de passe</label> (minimum {$password_length} caractères) {if $champs.passe.mandatory} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd class="help">
                Astuce : un mot de passe de quatre mots choisis au hasard dans le dictionnaire est plus sûr 
                et plus simple à retenir qu'un mot de passe composé de 10 lettres et chiffres.
            </dd>
            <dd class="help">
                Pas d'idée&nbsp;? Voici une suggestion choisie au hasard :
                <input type="text" readonly="readonly" title="Cliquer pour utiliser cette suggestion comme mot de passe" id="pw_suggest" value="{$passphrase}" autocomplete="off" />
            </dd>
            <dd><input type="password" name="passe" id="f_passe" value="{form_field name=passe}" pattern="{$password_pattern}" autocomplete="off" /></dd>
            <dt><label for="f_repasse">Encore le mot de passe</label> (vérification){if $champs.passe.mandatory} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="password" name="passe_confirmed" id="f_repasse" value="{form_field name=passe_confirmed}" pattern="{$password_pattern}" autocomplete="off" /></dd>
        </dl>
    </fieldset>

    {if $membre.secret_otp || $membre.clef_pgp}
    <fieldset>
        <legend>Options de sécurité</legend>
        <dl>
        {if $membre.secret_otp}
            {input type="checkbox" name="clear_otp" value="1" label="Désactiver l'authentification à double facteur TOTP"}
        {/if}
        {if $membre.clef_pgp}
            {input type="checkbox" name="clear_pgp" value="1" label="Supprimer la clé PGP associée au membre"}
        {/if}
        </dl>
    </fieldset>
    {/if}

    {if $session->canAccess('membres', Membres::DROIT_ADMIN) && $user.id != $membre.id}
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
        {csrf_field key="edit_member_"|cat:$membre.id}
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