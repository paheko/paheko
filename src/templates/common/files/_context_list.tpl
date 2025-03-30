<?php
assert(isset($path, $edit));

if (!isset($files)) {
	$files = Files\Files::list($path);
}

$can_upload = false;
$upload ??= $edit;
$use_trash ??= true;

if ($edit
	&& $upload
	&& Entities\Files\File::canCreate($path . '/')) {
	$can_upload = true;
}

$button_label ??= "Ajouter un fichier";

?>

{if $can_upload}
<div {enable_upload_here path=$path}>
	<p class="action-upload">
		{linkbutton shape="upload" href="!common/files/upload.php?p=%s"|args:$path target="_dialog" label=$button_label}
		<em>(ou glisser et déposer un fichier ici)</em>
	</p>
{/if}

<div class="files-list">
{foreach from=$files item="file"}
	<?php
	if (!$file->canRead()) {
		break;
	}

	$delete_shape = null;

	if ($edit) {
		if ($use_trash && $file->canMoveToTrash()) {
			$trash = '';
			$delete_shape = 'trash';
		}
		elseif (!$use_trash && $file->canDelete()) {
			$trash = '&trash=no';
			$delete_shape = 'delete';
		}
	}
	?>
	<figure class="file">
		<span class="thumb">{$file->link($session, 'auto')|raw}</span>
		<figcaption>
			{$file->link($session)|raw}
		</figcaption>
		<span class="actions">
			{linkbutton shape="download" href=$file->url(true) target="_blank" label="Télécharger"}
			{if $delete_shape}
				{linkbutton shape=$delete_shape target="_dialog" href="!common/files/delete.php?p=%s%s"|args:$file->path_uri():$trash label="Supprimer"}
			{/if}
		</span>
	</figure>
{foreachelse}
	{if !$can_upload}
		<em>—</em>
	{/if}
{/foreach}
</div>

{if $can_upload}
</div>
{/if}