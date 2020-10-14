{include file="admin/_head.tpl" title="Supprimer un compte" current="acc/charts"}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Supprimer un compte</legend>
		<h3 class="warning">
			Êtes-vous sûr de vouloir supprimer le compte «&nbsp;{$account.code} - {$account.label}&nbsp;»&nbsp;?
		</h3>
		<p class="help">
			Attention, le compte ne pourra pas être supprimé si des opérations y sont affectées.
		</p>
	</fieldset>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_accounts_delete_%s"|args:$account.id}
		<input type="submit" name="delete" value="Supprimer &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}