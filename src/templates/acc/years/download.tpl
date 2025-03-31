{include file="_head.tpl" title="Télécharger un exercice" current="acc/years"}

{form_errors}

<form method="post" action="{$self_url}" data-disable-progress="1">

<noscript>
	<p class="block error">Cette page nécessite javascript pour fonctionner.</p>
</noscript>

<fieldset>
	<legend>Téléchargement d'un exercice dans une archive ZIP</legend>
	<dl>
		<dt>Cocher ce que l'archive ZIP doit contenir :</dt>
		{foreach from=$files item="file" key="name"}
			{input type="checkbox" name=$name label=$name value=$file.url checked=$file.checked data-extension=$file.ext}
		{/foreach}
	</dd>
</fieldset>

<p class="submit">
	{csrf_field key=$csrf_key}
	{button type="submit" name="load" label="Télécharger" shape="download" class="main"}
	<p class="help">Le téléchargement peut prendre plusieurs minutes.</p>
</p>

<script type="text/javascript">
const year_name = {$year.label|escape:'json'};
{literal}
g.script('scripts/zip.js', () => {
	document.forms[0].onsubmit = (e) => {
		e.preventDefault();
		var files = [];

		document.forms[0].querySelectorAll('input[type=checkbox]:checked').forEach((f) => {
			files.push({extension: f.dataset.extension, url: f.value, name: f.name});
		})

		g.downloadAsZip(year_name, files, document.querySelector('p.submit input[type=hidden]'));
		return false;
	};
});
{/literal}
</script>


</form>

{include file="_foot.tpl"}