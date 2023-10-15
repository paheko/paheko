{include file="_head.tpl" title="Configuration du serveur" current="config"}

{include file="config/_menu.tpl" current=null}

{if $_GET.msg === 'OK'}
<p class="confirm block">L'opération a été réalisée.</p>
{/if}

{form_errors}

<h2 class="ruler">Stockage des fichiers</h2>

<form method="post" action="">
<dl class="large">
	<dd class="help">Taille des fichiers dans la base de données&nbsp;: {$db_size|size_in_bytes:false}</dd>
{if FILE_STORAGE_BACKEND === 'SQLite'}
	<dd>Paheko stocke le contenu des fichiers et leurs méta-données dans la base de données.</dd>
{elseif FILE_STORAGE_BACKEND === 'FileSystem'}
	<dd>Paheko stocke les méta-données des fichiers dans la base de données.</dd>
	<dd>Paheko stocke le contenu des fichiers dans le répertoire <code><?=FILE_STORAGE_CONFIG?></code></dd>
	<dt>Reconstruire les méta-données à partir du système de fichiers</dt>
	<dd>
		{button shape="reload" label="Scanner le répertoire" name="scan" type="submit"}
	</dd>
	<dd>
		Permet de mettre à jour les méta-données si vous avez réalisé des modifications externes à Paheko dans les fichiers
		(suppression, renommage, déplacement, etc.).
	</dd>
	<dd class="help">
		En cliquant sur ce bouton, Paheko va scanner le répertoire de stockage des fichiers,
		supprimer les méta-données fichiers qui n'existent plus sur le disque, et ajouter celles des fichiers qui sont apparus.
	</dd>
	{if !$db_size}
	<dt>Importer dans la base de données</dt>
	<dd>
		{button shape="import" label="Copier VERS la base de données" name="import" type="submit"}
	</dd>
	<dd class="help">
		En cliquant sur ce bouton, le contenu des fichiers sera recopié <strong>à l'intérieur de la base de données</strong>.<br />
		Utile pour migrer vers un stockage en base de données.
	</dd>
	{else}
	<dt>Exporter vers le répertoire de stockage</dt>
	<dd>
		{button shape="export" label="Copier DEPUIS la base de données" name="export" type="submit"}
	</dd>
	<dd>
		<p class="alert block">
			Les fichiers seront effacés de la base de données. Tout fichier local existant sera écrasé.
		</p>
	</dd>
	<dd class="help">
		En cliquant sur ce bouton, les fichiers seront créés <strong>dans le répertoire de stockage, à partir des informations de la base de données</strong>.<br />
		Utile pour migrer vers un stockage en répertoire local.
	</dd>
	{/if}
{/if}

</dl>
{csrf_field key=$csrf_key}
</form>

<h2 class="ruler">Configuration de Paheko</h2>

<table class="list">
	{foreach from=$constants key="key" item="value"}
	<tr>
		<th>{$key}</th>
		<td>
			{if $value === true}<samp>TRUE</samp>
			{elseif $value === false}<samp>TRUE</samp>
			{elseif $value === null}<em><samp>NULL</samp></em>
			{elseif is_array($value)}
				<code><?=var_export($value)?></code>
			{else}<code>{$value}</code>{/if}
		</td>
	</tr>
	{/foreach}
</table>

{include file="_foot.tpl"}