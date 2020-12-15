{include file="admin/_head.tpl" title="Inclure un fichier" current="web" body_id="transparent" is_popup=true}

{form_errors}

<form method="post" enctype="multipart/form-data" action="{$self_url}" id="f_upload">
	<fieldset>
		<legend>Téléverser un fichier</legend>
		<dl>
			{input type="file" name="file" label="Fichier" required=true}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="upload" label="Envoyer le fichier" shape="upload" class="main" id="f_submit"}
		</p>
	</fieldset>
</form>

<form method="get" action="#" style="display: none;" id="insertImage">
	<fieldset>
		<h3>Insérer une image dans le texte</h3>
		<dl>
			<dd class="image"></dd>
			<dt>Légende <i>(facultatif)</i></dt>
			<dd class="caption">
				<input type="text" name="f_caption" size="50" />
			</dd>
			<dt>Alignement&nbsp;:</dt>
			<dd class="align">
				<input type="button" name="gauche" value="À gauche" />
				<input type="button" name="centre" value="Au centre" />
				<input type="button" name="droite" value="À droite" />
			</dd>
			<dd class="cancel">
				<input type="reset" value="Annuler" />
			</dd>
		</dl>
	</fieldset>
</form>

{if !empty($images)}
<ul class="gallery">
{foreach from=$images item="file"}
	<li>
		<figure>
			<a href="{$file->url()}" data-id="{$file.id}"><img src="{$file->thumb_url()}" alt="" title="{$file.name}" /></a>
			<form class="actions" method="post" action="{$self_url}">
				{linkbutton shape="download" label="Télécharger" href=$file->url() target="_blank"}
				{csrf_field key=$csrf_key}
				<input type="hidden" name="delete" value="{$file.id}" />
				<noscript><input type="submit" value="Supprimer" /></noscript>
			</form>
		</figure>
	</li>
{/foreach}
</ul>
{/if}

{if !empty($fichiers)}
<table class="list">
	<tbody>
	{foreach from=$fichiers item="file"}
		<tr>
			<th>{$file.nom}</th>
			<td>{if $file.type}{$file.type}{/if}</td>
			<td class="actions">
				<form class="actions" method="post" action="{$self_url}">
					{linkbutton shape="download" label="Télécharger" href=$file.url target="_blank"}
					{csrf_field key=$csrf_key}
					<input type="hidden" name="delete" value="{$file.id}" />
					<noscript><input type="submit" value="Supprimer" /></noscript>
				</form>
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>
{/if}

{include file="admin/_foot.tpl"}