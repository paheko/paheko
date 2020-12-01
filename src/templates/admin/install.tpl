{include file="admin/_head.tpl" title="Garradin - Installation"}

{if $disabled}
    <p class="block error">Garradin est déjà installé.</p>
{else}
    <p class="help">
        Bienvenue dans Garradin !
        Veuillez remplir les quelques informations suivantes pour terminer
        l'installation.
    </p>

    {form_errors}

    <form method="post" action="{$self_url}">

    <fieldset>
        <legend>Informations sur l'association</legend>
        <dl>
            <dt><label for="f_nom_asso">Nom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom_asso" id="f_nom_asso" required="required" value="{form_field name=nom_asso}" /></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Création du compte administrateur</legend>
        <dl>
            <dt><label for="f_nom_membre">Nom et prénom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom_membre" id="f_nom_membre" required="required" value="{form_field name=nom_membre}" /></dd>
            <dt><label for="f_email_membre">Adresse E-Mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="email" name="email_membre" id="f_email_membre" required="required" value="{form_field name=email_membre}" /></dd>
            <dt><label for="f_passe_membre">Mot de passe</label> (minimum {$password_length} caractères) <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="help">
                Astuce : un mot de passe de quatre mots choisis au hasard dans le dictionnaire est plus sûr
                et plus simple à retenir qu'un mot de passe composé de 10 lettres et chiffres.
            </dd>
            <dd class="help">
                Pas d'idée&nbsp;? Voici une suggestion choisie au hasard :
                <input type="text" readonly="readonly" title="Cliquer pour utiliser cette suggestion comme mot de passe" id="pw_suggest" value="{$passphrase}" autocomplete="off" />
            </dd>
            <dd><input type="password" name="passe" id="f_passe_membre" value="{form_field name=passe}" pattern="{$password_pattern}" required="required" autocomplete="off" /></dd>
            <dt><label for="f_repasse_membre">Encore le mot de passe</label> (vérification) <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="password" name="passe_confirmed" id="f_repasse_membre" value="{form_field name=passe_confirmed}" pattern="{$password_pattern}" required="required" autocomplete="off" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="install"}
        {button type="submit" name="save" label="Terminer l'installation" shape="right" class="main"}
    </p>

    <script type="text/javascript" src="{$admin_url}static/scripts/loader.js"></script>

    <script type="text/javascript">
    {literal}
    g.script('scripts/password.js', () => {
        initPasswordField('pw_suggest', 'f_passe_membre', 'f_repasse_membre');
    });

    var form = $('form')[0];
    form.onsubmit = function () {
        $('#f_submit').style.opacity = 0;
        var loader = document.createElement('div');
        loader.className = 'loader install';
        loader.innerHTML = '<b>Garradin est en cours d\'installation…</b>';
        $('#f_submit').parentNode.appendChild(loader);
        animatedLoader(loader, 5);
    };
    {/literal}
    </script>

    </form>
{/if}

{include file="admin/_foot.tpl"}