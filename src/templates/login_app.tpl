{include file="_head.tpl" title="Accès par une application tiers" layout="public"}

{if $app_token == 'ok'}
	<p class="block confirm">L'application a bien été autorisée.</p>
	<div class="progressing block"></div>
	<p class="help">Vous pourrez fermer cette fenêtre quand l'application aura terminé l'autorisation.</p>
{else}
	<p class="alert block">Une application tiers demande à accéder aux fichiers de l'association.</p>
	<form method="post" action="{$self_url}">
		<fieldset>
			<legend>Confirmer l'accès</legend>
			<h3 class="warning">Autoriser l'application à accéder aux fichiers&nbsp;?</h3>
			<p class="help">L'application aura accès aux fichiers suivants&nbsp;:</p>
			<table class="list auto">
				<thead>
					<tr>
						<td>Section</td>
						<td>Lecture</td>
						<td>Modification</td>
						<td>Suppression</td>
					</tr>
				</thead>
				<tbody>
					{foreach from=$permissions key="name" item="access"}
					<tr>
						<td>{$name}</td>
						<td class="check">{if $access.read}{icon shape="check"}{/if}</td>
						<td class="check">{if $access.write}{icon shape="check"}{/if}</td>
						<td class="check">{if $access.delete}{icon shape="check"}{/if}</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
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