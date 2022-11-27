{include file="_head.tpl" title="Accès par une application tiers" layout="public"}

{if $app_token == 'ok'}
	<p class="block confirm">L'application a bien été autorisée.</p>
	<div class="progressing block"></div>
	<p class="help">Vous pourrez fermer cette fenêtre quand l'application aura terminé l'autorisation.</p>
{else}
	<p class="alert block">Une application tiers demande à accéder aux documents de l'association.</p>
	{form_errors}
	<form method="post" action="{$self_url}">
		<fieldset>
			<legend>Confirmer l'accès</legend>
			<h3 class="warning">Autoriser l'application à accéder aux documents&nbsp;?</h3>
			<div class="help">
				<p>L'application pourra&nbsp;:</p>
				<ul>
					{if $permissions.read}<li>Lire les fichiers</li>{/if}
					{if $permissions.write}<li>Modifier les fichiers</li>{/if}
					{if $permissions.delete}<li>Supprimer les fichiers</li>{/if}
				</ul>
			</div>

			<p class="actions">
				{csrf_field key=$csrf_key}
				{button type="submit" label="Autoriser l'accès" shape="right" class="main" name="confirm"}
			</p>
			<p class="submit">
				{button type="submit" label="Annuler" shape="left" name="cancel"}
			</p>
		</fieldset>
	</form>
{/if}

{include file="_foot.tpl"}