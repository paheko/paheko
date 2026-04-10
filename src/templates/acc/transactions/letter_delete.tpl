{include file="_head.tpl" title="Supprimer un lettrage" current="acc"}

<form method="post" action="" data-focus="1">
	<fieldset>
		<legend>Supprimer un lettrage</legend>
		<p class="alert block">
			Toutes les écritures concernant ce lettrage seront concernées.
		</p>
		<dl>
			{input type="text" name="letter" required=true label="Lettre"}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="delete" label="Supprimer ce lettrage" shape="delete" class="main"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}