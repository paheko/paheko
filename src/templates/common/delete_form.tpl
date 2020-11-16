{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>{$legend}</legend>
		<h3 class="warning">
			{$warning}
		</h3>
		{if isset($alert)}
		<p class="block alert">
			{$alert}
		</p>
		{/if}
		{if isset($info)}
		<p class="help">
			{$info}
		</p>
		{/if}
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="delete" label="Supprimer" shape="delete" class="main"}
	</p>

</form>