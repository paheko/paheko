{include file="_head.tpl" title="Remise à zéro" current="config" custom_css=["config.css"]}

{include file="config/_menu.tpl" current="advanced"}

{form_errors}
<form method="post" action="{$self_url_no_qs}">

<fieldset>
	<legend>Remise à zéro</legend>
	<div class="block error">
		<h3>Attention : toutes les données seront effacées&nbsp;!</h3>
		<ul>
			<li>Les membres seront supprimés, ainsi que les activités et l'historique d'inscription</li>
			<li>Les écritures et exercices comptables seront aussi supprimés, avec toutes les autres données comptables</li>
			<li>Le contenu du site web</li>
			<li>Les documents, etc.</li>
			<li>Bref : <strong>tout sera effacé !</strong></li>
		</ul>
		<p>Seul votre compte membre sera re-créé avec le même email et mot de passe.</p>
	</div>
	<p class="help">
		Une sauvegarde sera automatiquement créée avant de procéder à la remise à zéro.
	</p>
	<dl>
		{input type="password" name="checkme" label="Merci d'entrer ici votre mot de passe" help="Pour valider que vous désirez bien tout effacer !" required=true}
	</dl>
	<p class="submit">
		{csrf_field key="reset"}
		{button type="submit" name="reset_ok" label="Oui, je veux tout effacer et repartir de zéro" shape="delete" class="main"}
	</p>
</fieldset>

</form>


{include file="_foot.tpl"}