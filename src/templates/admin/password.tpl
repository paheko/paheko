{include file="admin/_head.tpl" title="Mot de passe oublié ou pas de mot de passe ?"}


{if !empty($sent)}
    <p class="block confirm">
        Un e-mail vous a été envoyé, cliquez sur le lien dans cet e-mail
        pour modifier votre mot de passe.
    </p>
    <p class="block alert">
        Si le message n'apparaît pas dans les prochaines minutes, vérifiez le dossier Spam ou Indésirables.
    </p>

{else}

    {form_errors}

    <form method="post" action="{$self_url_no_qs}">

        <fieldset>
            <legend>Recevoir un e-mail avec un nouveau mot de passe</legend>
            <p class="help">
                Inscrivez ici votre {$champ.title}.
                Nous vous enverrons un message vous indiquant un lien permettant de recevoir un
                nouveau mot de passe.
            </p>
            <dl>
                <dt><label for="f_id">{$champ.title}</label></dt>
                <dd><input type="text" name="id" id="f_id" value="{form_field name=id}" /></dd>
            </dl>
        </fieldset>

        <p class="submit">
            {csrf_field key="recoverPassword"}
            {button type="submit" name="recover" label="Envoyer" shape="right" class="main"}
        </p>

    </form>
{/if}

{include file="admin/_foot.tpl"}