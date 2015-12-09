{include file="admin/_head.tpl" title="Connexion"}

{if $error}
    <p class="error">
        {if $error == 'OTHER'}
            Une erreur est survenue, merci de réessayer.
        {else}
            Connexion impossible. Vérifiez l'adresse e-mail et le mot de passe.
        {/if}
    </p>
{/if}

{if !$ssl_enabled && $prefer_ssl}
    <p class="alert">
        <strong>Message de sécurité</strong><br />
        Nous vous conseillons de vous connecter sur la version <a href="{$own_https_url}">chiffrée (HTTPS) de cette page</a>
        pour vous connecter.
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Connexion</legend>
        <dl>
            <dt><label for="f_id">{$champ.title}</label></dt>
            <dd><input type="text" name="id" id="f_id" value="{form_field name=id}" /></dd>
            <dt><label for="f_passe">Mot de passe</label></dt>
            <dd><input type="password" name="passe" id="f_passe" value="" />
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
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="login"}
        <input type="submit" name="login" value="Se connecter &rarr;" />
    </p>

    <p class="help">
        <a href="{$www_url}admin/password.php">Pas de mot de passe ou mot de passe perdu ?</a>
    </p>

</form>

{include file="admin/_foot.tpl"}