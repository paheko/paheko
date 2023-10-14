{include file="_head.tpl" title="Commencer un exercice" current="acc/years"}

{if isset($_GET.from)}
	<p class="confirm block"><strong>L'exercice a bien été clôturé.</strong><br />Vous pouvez commencer un nouvel exercice ci-dessous.</p>
{/if}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Commencer un nouvel exercice</legend>
		<dl>
			{input type="select_groups" options=$charts name="id_chart" label="Plan comptable" required=true source=$year}
			<dd class="help">
				Il ne sera pas possible de changer le plan comptable une fois l'exercice ouvert.<br />
				Il ne sera également pas possible de modifier ou supprimer un compte du plan comptable si le compte est utilisé dans un autre exercice déjà clôturé.<br />
				Si vous souhaitez modifier le plan comptable pour ce nouvel exercice, il est recommandé de créer un nouveau plan comptable, recopié à partir de l'ancien plan comptable. Ainsi tous les comptes seront modifiables et supprimables.
			</dd>
			<dd class="help">{linkbutton shape="settings" label="Gestion des plans comptables" href="!acc/charts/"}</dd>
			{input type="text" name="label" label="Libellé" required=true source=$year}
			{input type="date" label="Début de l'exercice" name="start_date" required=true  source=$year}
			{input type="date" label="Fin de l'exercice" name="end_date" required=true source=$year}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_years_new"}
		{if isset($_GET.from)}
			{linkbutton shape="left" href="./" label="Ne pas créer de nouvel exercice"}
		{else}
			{linkbutton shape="left" href="./" label="Annuler"}
		{/if}
		{button type="submit" name="new" label="Créer ce nouvel exercice" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}