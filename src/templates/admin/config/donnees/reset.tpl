{include file="admin/_head.tpl" title="Remise à zéro" current="config"}

{include file="admin/config/_menu.tpl" current="donnees"}

{include file="admin/config/donnees/_menu.tpl" current="reset"}

{form_errors}

{if $ok !== null}
    <p class="block confirm">La remise à zéro a été effectuée. Une sauvegarde a également été créée.</p>
    </p>
{/if}

<form method="post" action="{$self_url_no_qs}">

<fieldset>
    <legend>Remise à zéro</legend>
	<p class="block error">
		Attention : toutes les données seront effacées&nbsp;! Ceci inclut les membres, les écritures comptables, les pages du wiki, etc.
        Seul votre compte membre sera re-créé avec le même email et mot de passe.
	</p>
    <p class="help">
        Une sauvegarde sera automatiquement créée avant de procéder à la remise à zéro.
    </p>
    <dl>
        <dt><label for="f_passe_verif">Votre mot de passe</label> (pour vérification)</dt>
        <dd><input type="password" name="passe_verif" id="f_passe_verif" /></dd>
    </dl>
    <p>
        {csrf_field key="reset"}
        <input type="submit" name="reset_ok" value="Oui, je veux remettre à zéro" />
    </p>
</fieldset>

</form>


{include file="admin/_foot.tpl"}