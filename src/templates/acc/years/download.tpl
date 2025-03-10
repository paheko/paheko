{include file="_head.tpl" title="Télécharger un exercice" current="acc/years"}

{form_errors}

<form method="post" action="{$self_url}">

<noscript>
	<p class="block error">Cette page nécessite javascript pour fonctionner.</p>
</noscript>

<fieldset>
	<legend>Téléchargement d'un exercice dans une archive ZIP</legend>
	<dl>
		<dt>Cocher ce que l'archive ZIP doit contenir :</dt>
		{foreach from=$files item="file" key="name"}
			{input type="checkbox" name="%s.%s"|args:$name:$file.ext label=$name value=$file.url checked=$file.checked data-name=$name}
		{/foreach}
	</dd>
</fieldset>

<p class="submit">
	{csrf_field key=$csrf_key}
	{button type="submit" name="load" label="Télécharger" shape="download" class="main"}
	<p class="help">Le téléchargement peut prendre plusieurs minutes.</p>
</p>

<script type="module">
"use strict";
const year_name = {$year.label|escape:'json'};
import * as clientZip from "{$admin_uri}static/scripts/lib/client-zip.min.js?{$version_hash}";
{literal}
async function downloadAll() {
	var data = new FormData();
	var token = document.querySelector('p.submit input[type=hidden]');
	data.append(token.name, token.value);
	data.append('download', 'yes');

	var bg = document.createElement('div');
	bg.className = 'overlay';
	var msg = document.createElement('div');
	msg.className = 'message';
	bg.appendChild(msg);

	document.body.appendChild(bg);
	document.body.classList.add('loading');

	var files = document.forms[0].querySelectorAll('input[type=checkbox]:checked');

	async function *downloadFiles() {
		for await (const f of files) {
			msg.innerText = 'Téléchargement — ' + f.dataset.name;
			yield {name: f.name, input: await fetch(f.value, {method: 'POST', body: data})};
			// Wait a bit between files, to get around rate limiting on the server
			await new Promise(r => setTimeout(r, 1000));
		}
	}

	const zip = await clientZip.downloadZip(downloadFiles()).blob();

	// make and click a temporary link to download the Blob
	const link = document.createElement('a')
	link.href = URL.createObjectURL(zip)
	link.download = year_name + '.zip';
	link.click()
	link.remove()

	bg.remove();
	document.body.classList.remove('loading');

	if (typeof window.parent.g === 'undefined' || !window.parent.g.dialog) {
		return;
	}

	window.parent.g.closeDialog();
}

document.forms[0].onsubmit = (e) => {
	e.preventDefault();
	downloadAll();
	return false;
};
{/literal}
</script>


</form>

{include file="_foot.tpl"}