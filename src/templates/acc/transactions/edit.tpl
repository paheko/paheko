{include file="admin/_head.tpl" title="Modification d'une écriture" current="acc/new" js=1}

<form method="post" action="{$self_url}" enctype="multipart/form-data">
	{form_errors}

	<fieldset>
		<legend>Informations</legend>
		<dl>
			{input source=$transaction type="text" name="label" label="Libellé général" required=1}
			{input source=$transaction type="date" name="date" label="Date" required=1}
		</dl>
	</fieldset>

	{* Saisie avancée *}
	<fieldset data-types="advanced">
		{include file="acc/transactions/_lines_form.tpl" chart_id=$chart.id}
	</fieldset>

	<fieldset>
		<legend>Détails</legend>
		<dl>
			{input default=$linked_users type="list" multiple=true name="users" label="Membres associés" target="membres/selector.php"}
			{input source=$transaction type="text" name="reference" label="Numéro de pièce comptable"}
			{input source=$transaction type="textarea" name="notes" label="Remarques" rows=4 cols=30}

			{input source=$transaction type="file" name="file" label="Ajouter un fichier joint"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_edit_%d"|args:$transaction.id}
		<input type="submit" name="save" value="Enregistrer &rarr;" />
	</p>

</form>

{literal}
<script type="text/javascript" defer="defer" async="async">
g.script('scripts/accounting.js', () => { initTransactionForm(); });
</script>
{/literal}

{include file="admin/_foot.tpl"}