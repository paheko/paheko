{include file="admin/_head.tpl" title="Garradin - Installation" menu=false}

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
        {input type="text" label="Nom de l'association" required=true name="name"}
    </dl>
</fieldset>

<fieldset>
    <legend>Création du compte administrateur</legend>
    <dl>
        {input type="text" label="Nom et prénom" required=true name="user_name"}
        {input type="email" label="Adresse E-Mail" required=true name="user_email"}
        {password_change label="Mot de passe" required=true name="user_password"}
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
    initPasswordField('user_password');
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


{include file="admin/_foot.tpl"}