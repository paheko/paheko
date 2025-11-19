{include file="_head.tpl" title="Dépôt en banque : %s — %s"|args:$account.code:$account.label current="acc/accounts"}

{form_errors}

<form method="post" action="{$self_url}">
	{if $mode === 'mark'}
		<fieldset>
		<legend>Marquer manuellement des lignes comme déposées</legend>
		<h3 class="warning">
			{{Marquer %n ligne comme déposée ?}{Marquer %n lignes comme déposées ?} n=$checked|count}
		</h3>

		<p class="alert block">
			En cliquant sur le bouton ci-dessous, les lignes cochées seront marquées comme ayant été déposées.<br /><strong>Il ne sera pas possible de les remettre dans la liste des écritures à déposer en banque.</strong>
		</p>

		<p class="submit">
			{csrf_field key=$csrf_key}
			<input type="hidden" name="mark" value="1" />
			<input type="hidden" name="deposit" value="{$checked_json}" />
			{button type="submit" name="confirm_mark" label="Marquer comme déposées" class="main" shape="right"}
		</p>
	{else}

		<fieldset>
			<legend>Détails de l'écriture de dépôt</legend>
			<dl>
				<dt><strong>Nombre de lignes cochées</strong></dt>
				<dd><mark>{$checked|count}</mark></dd>
				{input type="text" name="label" label="Libellé" required=1 default="Dépôt en banque"}
				{input type="date" name="date" default=$date label="Date" required=1}
				{input type="money" name="amount" label="Montant" required=1 default=$total}
				{input type="list" target="!acc/charts/accounts/selector.php?id_chart=%d&types=%d"|args:$account.id_chart:$types name="account_transfer" label="Compte de dépôt" required=1}
				{input type="text" name="reference" label="Numéro de pièce comptable"}
				{input type="textarea" name="notes" label="Remarques" rows=4 cols=30}
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key=$csrf_key}
			<input type="hidden" name="create" value="1" />
			<input type="hidden" name="deposit" value="{$checked_json}" />
			{button type="submit" name="save" label="Enregistrer" class="main" shape="right"}
		</p>

	{/if}
</form>