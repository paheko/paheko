{include file="admin/_head.tpl" title="Commencer un exercice" current="acc/years"}

<nav class="tabs">
	<ul>
		<li><a href="./">Exercices</a></li>
		<li class="current"><a href="new.php">Nouvel exercice</a></li>
	</ul>
</nav>

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Commencer un nouvel exercice</legend>
		<dl>
			{input type="select_groups" options=$charts name="id_chart" label="Plan comptable" required=true}
			<dd class="help">Attention, il ne sera pas possible de modifier ou supprimer un compte du plan comptable si le compte est utilisé dans un exercice clôturé.<br />
				Si vous souhaitez modifier le plan comptable pour ce nouvel exercice, il est recommandé de créer un nouveau plan comptable, recopié à partir de l'ancien plan comptable. Ainsi tous les comptes seront modifiables et supprimables.</dd>
			<dd class="help">{linkbutton shape="settings" label="Gestion des plans comptables" href="!acc/charts/"}</dd>
			{input type="text" name="label" label="Libellé" required=true}
			{input type="date" label="Début de l'exercice" name="start_date" required=true default=$start_date}
			{input type="date" label="Fin de l'exercice" name="end_date" required=true default=$end_date}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_years_new"}
		{button type="submit" name="new" label="Créer ce nouvel exercice" shape="right" class="main"}
	</p>

</form>

{include file="admin/_foot.tpl"}