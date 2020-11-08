{include file="admin/_head.tpl" title="Connexion" js=1}

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
            <dd><label><input type="checkbox" name="permanent" value="1" /> Rester connecté‑e</label></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="login"}
        <input type="submit" name="login" value="Se connecter &rarr;" />
    </p>

    <p class="help">
        <a href="{$admin_url}password.php">Pas de mot de passe ou mot de passe perdu ?</a>
    </p>

</form>

<script type="text/javascript">
g.enhancePasswordField($('#f_passe'));
</script>

{include file="admin/_foot.tpl"}