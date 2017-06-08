{include file="admin/_head.tpl" title="Connexion — double facteur"}

{form_errors}
{show_error if=$fail message="Code incorrect. Vérifiez que votre téléphone est à l'heure."}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Authentification à double facteur</legend>
        <dl>
            <dt><label for="f_code">Code TOTP</label></dt>
            <dd class="help">Entrez ici le code donné par l'application d'authentification double facteur.</dd>
            <dd><input type="text" name="code" id="f_code" value="{form_field name=code}" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="otp"}
        <input type="submit" name="login" value="Se connecter &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}