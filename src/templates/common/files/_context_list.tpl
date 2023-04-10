<?php
assert(isset($path, $edit));

if (!isset($files)) {
	$files = Files\Files::list($path);
}

$can_upload = false;

if ($edit
	&& Entities\Files\File::canCreate($path . '/')) {
	$can_upload = true;
}

?>

{if $can_upload}
<p>
	{linkbutton shape="upload" href="!common/files/upload.php?p=%s"|args:$path target="_dialog" label="Ajouter un fichier"}
</p>
{/if}

<div class="files-list">
{foreach from=$files item="file"}
	<?php
	if (!$file->canRead()) {
		break;
	}
	?>
	<aside class="file">
		<figure>
			<span>{$file->link($session, 'auto')|raw}</span>
			<figcaption>
				{$file->link($session)|raw}
			</figcaption>
		</figure>
		{linkbutton shape="download" href=$file->url(true) target="_blank" label="Télécharger"}
		{if $edit && $file->canDelete()}
			{linkbutton shape="delete" target="_dialog" href="!common/files/delete.php?p=%s"|args:$file.path label="Supprimer"}
		{/if}
	</aside>
{foreachelse}
	{if !$can_upload}
		<em>--</em>
	{/if}
{/foreach}
</div>