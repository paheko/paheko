{include file="admin/_head.tpl" title="Garradin - Installation"}

<h1>Connexion</h1>

{if $error}
    <p class="error">
        {if $error == 'OTHER'}
            Une erreur est survenue, merci de réessayer.
        {else}
            Connexion impossible. Vérifiez l'adresse e-mail et le mot de passe.
        {/if}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Connexion</legend>
        <dl>
            <dt><label for="f_email">Adresse E-Mail</label></dt>
            <dd><input type="email" name="email" id="f_email" value="{form_field name='email'}" /></dd>
            <dt><label for="f_passe">Mot de passe</label></dt>
            <dd><input type="password" name="passe" id="f_passe" value="" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="login"}
        <input type="submit" name="login" value="Se connecter &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}