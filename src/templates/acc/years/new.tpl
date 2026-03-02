{include file="_head.tpl" title="Créer un nouvel exercice" current="acc/years"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Informations</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=true source=$year}
			{input type="select_groups" options=$charts name="id_chart" label="Plan comptable" required=true source=$year default_empty=$chart_selector_default}
			<dd>{linkbutton shape="settings" label="Gestion des plans comptables" href="!acc/charts/"}</dd>
		</dl>
		<p class="alert block">
			Attention : il ne sera plus possible de changer le plan comptable, une fois l'exercice créé.
		</p>
		<p class="help block">
			Il ne sera également pas possible de modifier ou supprimer un compte du plan comptable si le compte est utilisé dans un autre exercice déjà clôturé.<br />
			Si vous souhaitez modifier le plan comptable pour ce nouvel exercice, il est recommandé de créer un nouveau plan comptable, recopié à partir de l'ancien plan comptable. Ainsi tous les comptes seront modifiables et supprimables.<br />
		</p>
	</fieldset>

	<fieldset>
		<legend>Dates</legend>
		<dl>
			{input type="date" label="Début de l'exercice" name="start_date" required=true  source=$year}
			{input type="date" label="Fin de l'exercice" name="end_date" required=true source=$year}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_years_new"}
		{button type="submit" name="new" label="Créer ce nouvel exercice" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}