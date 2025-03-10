{include file="_head.tpl" title="Export d'exercice" current="acc/years"}

{form_errors}

<form method="get" action="{$self_url}" data-disable-progress="1">

<fieldset>
	<legend>Export du journal général</legend>
	<dl>
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
		<dt>Format d'export</dt>
		{input type="radio" name="format" value="ods" default="ods" label="LibreOffice" help="également lisible par Excel, Google Docs, etc."}
		{input type="radio" name="format" value="csv" label="CSV"}
		{input type="radio" name="format" value="xlsx" label="Excel"}
	</dl>
	<dl class="format_fec">
		{input type="radio" name="format" value="fec" label="Fichier conforme FEC" help="Pour transmettre à un expert-comptable par exemple"}
	</dl>
</fieldset>

<p class="submit">
	<input type="hidden" name="year" value="{$year.id}" />
	{button type="submit" name="load" label="Télécharger" shape="download" class="main"}
</p>

<script type="text/javascript">
{literal}
function selectFormat() {
	var fec = document.forms[0].type.value === 'fec';
	g.toggle('.format_fec', fec);
}
$('input[name="type"]').forEach(e => e.onchange = selectFormat);
selectFormat();
{/literal}
</script>


</form>

{include file="_foot.tpl"}