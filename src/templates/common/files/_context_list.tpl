<?php
assert(isset($path, $edit));

if (!isset($files)) {
	$files = Files\Files::list($path);
}

$can_upload = false;
$trash = isset($use_trash) && !$use_trash ? '&trash=no' : '';

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
		</figure>
		<figcaption>
			{$file->link($session)|raw}
		</figcaption>
		<span>
			{linkbutton shape="download" href=$file->url(true) target="_blank" label="Télécharger"}
			{if $edit && $file->canDelete()}
				{linkbutton shape="delete" target="_dialog" href="!common/files/delete.php?p=%s%s"|args:$file.path:$trash label="Supprimer"}
			{/if}
		</span>
	</aside>
{foreachelse}
	{if !$can_upload}
		<em>—</em>
	{/if}
{/foreach}
</div>