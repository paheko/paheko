{include file="admin/_head.tpl" title="Connexion"}

{form_errors}

{if $changed}
    <p class="block confirm">
        Votre mot de passe a bien été modifié.<br />
        Vous pouvez maintenant l'utiliser pour vous reconnecter.
    </p>
{/if}

{if !$ssl_enabled && $prefer_ssl}
    <p class="block alert">
        <strong>Message de sécurité</strong><br />
        Nous vous conseillons de vous connecter sur la version <a href="{$own_https_url}">chiffrée (HTTPS) de cette page</a>
        pour vous connecter.
    </p>
{/if}

<p class="block error" style="display: none;" id="old_browser">
    Le navigateur que vous utilisez n'est pas supporté. Des fonctionnalités peuvent ne pas fonctionner.<br />
    Merci d'utiliser un navigateur web moderne comme <a href="https://www.getfirefox.com/" target="_blank">Firefox</a> ou <a href="https://vivaldi.com/fr/" target="_blank">Vivaldi</a>.
</p>

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Connexion</legend>
        <dl>
            <dt><label for="f_id">{$id_field_name}</label></dt>
            <dd><input type="text" name="_id" id="f_id" value="{form_field name=_id}" /></dd>
            <dt><label for="f_passe">Mot de passe</label></dt>
            <dd><input type="password" name="password" id="f_passe" value="" autocomplete="current-password" />
                {if $ssl_enabled}
                    <b class="icn confirm" title="Connexion chiffrée">&#x1f512;</b>
                    <span class="confirm">Connexion sécurisée</span>
                {else}
                    <b class="icn error" title="Connexion non chiffrée">&#x1f513;</b>
                    {if $prefer_ssl}
                        <span class="error">Connexion non-sécurisée&nbsp;!</span>
                        <a href="{$own_https_url}">Se connecter en HTTPS (sécurisé)</a>
                    {else}
                        <span class="alert">Connexion non-sécurisée</span>
                    {/if}
                {/if}
            </dd>
            {input type="checkbox" name="permanent" value="1" label="Rester connecté‑e" help="recommandé seulement sur ordinateur personnel"}
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="login"}
        {button type="submit" name="login" label="Se connecter" shape="right" class="main"}
    </p>

    <p class="help">
        <a href="{$admin_url}password.php">Pas de mot de passe ou mot de passe perdu ?</a>
    </p>

</form>

{literal}
<script type="text/javascript">
if (window.navigator.userAgent.match(/MSIE|Trident\/|Edge\//)) {
    document.getElementById('old_browser').style.display = 'block';
}

g.enhancePasswordField($('#f_passe'));
</script>
{/literal}

{include file="admin/_foot.tpl"}