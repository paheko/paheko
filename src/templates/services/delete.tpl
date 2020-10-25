{include file="admin/_head.tpl" title="Supprimer une activité" current="membres/services"}

{include file="services/_nav.tpl" current="index"}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Supprimer cette activité ?</legend>
		<h3 class="warning">
			Êtes-vous sûr de vouloir supprimer l'activité «&nbsp;{$service.label}&nbsp;» ?
		</h3>
		<p class="alert">
			Attention, cela supprimera également l'historique des membres inscrits à cette activité, ainsi que les rappels associés.
		</p>
		<p class="help">
			Si des écritures comptables sont liées à l'historique des activités, elles ne seront pas supprimées,
			et la comptabilité demeurera inchangée.
		</p>
	</fieldset>

	<p class="submit">
		{csrf_field key="service_delete_"|cat:$service.id}
		<input type="submit" name="delete" value="Supprimer &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}