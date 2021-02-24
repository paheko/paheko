{if !empty($can_upload) && !empty($parent_path)}
<p class="actions">
	{linkbutton shape="upload" href="!common/files/upload.php?p=%s"|args:$parent_path target="_dialog" label="Ajouter un fichier"}
</p>
{/if}

<div class="files-list">
	{foreach from=$files item="file"}
	<aside class="file">
		{if $file.image}
			<figure>
				<a target="_blank" href="{$file->url()}"><img src="{$file->thumb_url()}" alt="" /></a>
				<figcaption>
					<a target="_blank" href="{$file->url()}">{$file.name}</a>
					<small>({$file.mime}, {$file.size|size_in_bytes})</small>
				</figcaption>
			</figure>
		{else}
			<a target="_blank" href="{$file->url()}">{$file.name}</a>
			<small>({$file.type}, {$file.size|size_in_bytes})</small>
		{/if}
		{linkbutton shape="download" href=$file->url(true) target="_blank" label="Télécharger"}
		{if $file->checkDeleteAccess($session)}
			{linkbutton shape="delete" target="_dialog" href="!common/files/delete.php?p=%s"|args:$file->path() label="Supprimer"}
		{/if}
	</aside>
	{/foreach}
</div>