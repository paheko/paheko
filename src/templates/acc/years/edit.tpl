{include file="admin/_head.tpl" title="Modifier un exercice" current="acc/years"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Modifier un exercice</legend>
		<dl>
			{input type="text" label="Libellé" name="label" source=$year required=true}
			{input type="date" label="Début de l'exercice" name="start_date" source=$year required=true}
			{input type="date" label="Fin de l'exercice" name="end_date" source=$year required=true}
			{input type="checkbox" label="Déplacer les écritures postérieures dans un autre exercice" value=1 name="split"}
		</dl>
		<dl class="split_year">
			<dd class="help">En cochant cette case, toute écriture située <em>après</em> la date de fin indiquée ci-dessus sera déplacée dans l'exercice sélectionné ci-dessous.</dd>
			{input type="select" name="split_year" options=$split_years label="Nouvel exercice à utiliser" help="Les écritures situées après la date de fin seront transférées dans cet exercice"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_years_edit_%s"|args:$year.id}
		{button type="submit" name="edit" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{literal}
<script type="text/javascript">
let split = $('#f_split_1');
g.toggle('.split_year', split.checked);

split.onchange = () => {
	g.toggle('.split_year', split.checked);
};
</script>
{/literal}

{include file="admin/_foot.tpl"}