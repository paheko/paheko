{include file="_head.tpl" title="Restaurer les documents et fichiers joints" current="config"}

{include file="config/_menu.tpl" current="backup"}

{include file="config/backup/_menu.tpl" current="restore"}

{if $ok}
<p class="confirm block">La restauration a été effectuée.</p>
{/if}

{if $failed}
<p class="alert block">{$failed} fichiers n'ont pas pu être restaurés car ils dépassaient la taille autorisée.</p>
{/if}

{form_errors}

<form method="post" action="{$self_url_no_qs}" id="restoreDocuments" style="display: none;" enctype="multipart/form-data" data-disable-progress="1">

<fieldset>
	<legend>Restaurer les fichiers avec une archive ZIP de sauvegarde</legend>
	<p class="help">
		Sélectionner ici une sauvegarde (archive ZIP) des documents pour les restaurer.
	</p>
	<dl>
		{input type="file" name="file" label="Archive ZIP à restaurer" no_size_limit=true required=true}
	</dl>
	<p class="alert block">
		Les fichiers existants qui portent le même nom seront écrasés. Les documents existants qui ne figurent pas dans la sauvegarde ne seront pas affectés.
	</p>
	<p class="submit">
		{csrf_field key="files_restore"}
		{button type="submit" name="restore" label="Restaurer cette sauvegarde des documents" shape="upload" class="main"}
	</p>
</fieldset>

</form>

<script type="text/javascript">
g.script('scripts/lib/unzipit.min.js');
g.script('scripts/unzip_restore.js');
</script>

{include file="_foot.tpl"}