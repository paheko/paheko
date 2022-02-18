{include file="admin/_head.tpl" title="Export d'exercice" current="acc/years"}

<nav class="acc-year">
	<h4>Exercice sélectionné&nbsp;:</h4>
	<h3>{$year.label} — {$year.start_date|date_short} au {$year.end_date|date_short}</h3>
</nav>

<nav class="tabs">
	<ul>
		{if !$year.closed}
		<li><a href="{$admin_url}acc/years/import.php?year={$year.id}">Import</a></li>
		{/if}
		<li class="current"><a href="{$admin_url}acc/years/import.php?year={$year.id}">Export</a></li>
	</ul>
</nav>

{form_errors}

<form method="get" action="{$self_url}">

<fieldset>
	<legend>Export du journal général</legend>
	<dl>
		<dt>Format d'export</dt>
		{input type="radio" name="format" value="ods" default="ods" label="Tableur" help="pour LibreOffice ou autre tableur"}
		{input type="radio" name="format" value="csv" label="CSV"}
		<dt>Type d'export</dt>
		{foreach from=$types key="type" item="info"}
		{input type="radio-btn" name="type" value=$type label=$info.label help=$info.help default="full"}
		<dd class="help example">
			Exemple :
			<table class="list auto">
				{foreach from=$examples[$type] item="row"}
				<tr>
					{foreach from=$row item="v"}
					<td>{$v}</td>
					{/foreach}
				</tr>
				{/foreach}
			</table>
		</dd>
		{/foreach}
	</dl>
</fieldset>

<p class="submit">
	<input type="hidden" name="year" value="{$year.id}" />
	{button type="submit" name="load" label="Télécharger" shape="download" class="main"}
</p>



</form>

{include file="admin/_foot.tpl"}