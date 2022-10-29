{include file="admin/_head.tpl" title="Gestion des plans comptables" current="acc/years"}

{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
	{include file="./_nav.tpl" current="charts"}
{/if}

{if $_GET.msg == 'OPEN'}
<p class="block alert">
	Il n'existe aucun exercice ouvert.
	{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		Merci d'en <a href="{$admin_url}acc/years/new.php">créer un nouveau</a> pour pouvoir saisir des écritures.
	{/if}
</p>
{/if}

{if count($list)}
	<table class="list">
		<thead>
			<td>Pays</td>
			<th>Libellé</th>
			<td>Type</td>
			<td>Archivé</td>
			<td></td>
		</thead>
		<tbody>
			{foreach from=$list item="item"}
				<tr{if $item.archived} class="disabled"{/if}>
					<td>{$item.country|get_country_name}</td>
					<th><a href="{$admin_url}acc/charts/accounts/?id={$item.id}">{$item.label}</a></th>
					<td>{if $item.code}Officiel{else}Personnel{/if}</td>
					<td>{if $item.archived}<em>Archivé</em>{/if}</td>
					<td class="actions">
						{linkbutton shape="star" label="Comptes favoris" href="!acc/charts/accounts/?id=%d"|args:$item.id}
						{linkbutton shape="menu" label="Tous les comptes" href="!acc/charts/accounts/all.php?id=%d"|args:$item.id}
						{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
							{linkbutton shape="edit" label="Modifier" href="!acc/charts/edit.php?id=%d"|args:$item.id}
							{linkbutton shape="export" label="Export CSV" href="!acc/charts/export.php?id=%d"|args:$item.id}
							{linkbutton shape="export" label="Export tableur" href="!acc/charts/export.php?id=%d&ods"|args:$item.id}
							{if !$item.code && !$item.archived}
								{linkbutton shape="delete" label="Supprimer" href="!acc/charts/delete.php?id=%d"|args:$item.id}
							{/if}
						{/if}
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{/if}

{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
	<form method="post" action="{$self_url_no_qs}" enctype="multipart/form-data">
		<h2 class="ruler">Ajouter un nouveau plan comptable</h2>
		<fieldset>
			<legend>Créer un nouveau plan comptable</legend>
			<dl>
				{input type="radio-btn" name="type" value="install" label="Installer un autre plan comptable officiel"}
				{input type="radio-btn" name="type" value="copy" label="Recopier un plan comptable pour le modifier"}
				{input type="radio-btn" name="type" value="import" label="Importer un plan comptable personnel" help="À partir d'un tableau (CSV, Office, etc.)"}
			</dl>
			<dl class="type-copy">
				{input type="select_groups" name="copy" options=$charts_groupped label="Recopier depuis" required=1 default=$from}
				{input type="text" name="label" label="Libellé" required=1}
				{input type="select" name="country" label="Pays" required=1 options=$country_list default=$config.pays}
			</dl>
			<dl class="type-install">
				{input type="select" name="install" label="Plan comptable" required=true options=$install_list}
			</dl>
			<dl class="type-import">
				{input type="text" name="label" label="Libellé" required=1}
				{input type="select" name="country" label="Pays" required=1 options=$country_list default=$config.pays}
				{input type="file" name="file" label="Fichier à importer" accept="csv" required=1}
				<dd class="help"> {* FIXME utiliser _csv_help.tpl ici ! *}
					Règles à suivre pour créer le fichier&nbsp;:
					<ul>
						<li>Le fichier doit comporter les colonnes suivantes : <em>{$columns}</em></li>
						<li>Suggestion : pour obtenir un exemple du format attendu, faire un export d'un plan comptable existant</li>
					</ul>
				</dd>
			</dl>
			<p class="submit type-all">
				{csrf_field key="acc_charts_new"}
				{button type="submit" name="new" label="Créer" shape="right" class="main"}
			</p>
		</fieldset>
	</form>
{/if}

{include file="admin/_foot.tpl"}