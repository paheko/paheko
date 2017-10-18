{include file="admin/_head.tpl" title="Garradin - Installation" js=1}

{if $disabled}
    <p class="error">Garradin est déjà installé.</p>
{else}
    <p class="intro">
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
            <dt><label for="f_email_asso">Adresse E-Mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="email" name="email_asso" id="f_email_asso" required="required" value="{form_field name=email_asso}" /></dd>
            <dt><label for="f_adresse_asso">Adresse postale</label></dt>
            <dd><textarea cols="50" rows="5" name="adresse_asso" id="f_adresse_asso">{form_field name=adresse_asso}</textarea></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Informations sur le premier membre</legend>
        <dl>
            <dt><label for="f_nom_membre">Nom et prénom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom_membre" id="f_nom_membre" required="required" value="{form_field name=nom_membre}" /></dd>
            <dt><label for="f_cat_membre">Catégorie du membre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="tip">Par exemple : bureau, conseil d'administration, présidente, trésorier, etc.</dd>
            <dd><input type="text" name="cat_membre" id="f_cat_membre" required="required" value="{form_field name=cat_membre}" /></dd>
            <dt><label for="f_email_membre">Adresse E-Mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="email" name="email_membre" id="f_email_membre" required="required" value="{form_field name=email_membre}" /></dd>
            <dt><label for="f_passe_membre">Mot de passe</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="help">
                Astuce : un mot de passe de quatre mots choisis au hasard dans le dictionnaire est plus sûr 
                et plus simple à retenir qu'un mot de passe composé de 10 lettres et chiffres.
            </dd>
            <dd class="help">
                Pas d'idée&nbsp;? Voici une suggestion choisie au hasard :
                <input type="text" readonly="readonly" title="Cliquer pour utiliser cette suggestion comme mot de passe" id="pw_suggest" value="{$passphrase}" autocomplete="off" />
            </dd>
            <dd><input type="password" name="passe" id="f_passe_membre" value="{form_field name=passe}" pattern=".{ldelim}6,{rdelim}" required="required" /></dd>
            <dt><label for="f_repasse_membre">Encore le mot de passe</label> (vérification) <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="password" name="passe_confirmed" id="f_repasse_membre" value="{form_field name=passe_confirmed}" pattern=".{ldelim}6,{rdelim}" required="required" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="install"}
        <input type="submit" id="f_submit" name="save" value="Terminer l'installation &rarr;" />
    </p>

    <script type="text/javascript" src="{$admin_url}static/scripts/loader.js"></script>

    <script type="text/javascript">
    {literal}
    g.script('scripts/password.js').onload = function () {
        initPasswordField('pw_suggest', 'f_passe_membre', 'f_repasse_membre');
    };
    
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