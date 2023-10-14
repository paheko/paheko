{include file="_head.tpl" title="Gestion des plans comptables" current="acc/years"}

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

{form_errors}

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
					<td>{if $item.country}{$item.country|get_country_name}{else}-Autre-{/if}</td>
					<th><a href="{$admin_url}acc/charts/accounts/?id={$item.id}">{$item.label}</a></th>
					<td>{if $item.code}Officiel{else}Personnel{/if}</td>
					<td>{if $item.archived}<em>Archivé</em>{/if}</td>
					<td class="actions">
						{if $item.country}
							{linkbutton shape="star" label="Comptes usuels" href="!acc/charts/accounts/?id=%d"|args:$item.id}
						{/if}
						{linkbutton shape="menu" label="Tous les comptes" href="!acc/charts/accounts/all.php?id=%d"|args:$item.id}
						{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
							{linkbutton shape="edit" label="Modifier" href="!acc/charts/edit.php?id=%d"|args:$item.id target="_dialog"}
							{if $item->canDelete()}
								{linkbutton shape="delete" label="Supprimer" href="!acc/charts/delete.php?id=%d"|args:$item.id target="_dialog"}
							{/if}
						{/if}
						{exportmenu class="menu-btn-right" href="export.php?id=%d"|args:$item.id suffix="format="}
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{/if}

{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
	<form method="post" action="{$self_url_no_qs}" enctype="multipart/form-data">
		<fieldset>
			<legend>Créer un nouveau plan comptable</legend>
			<dl>
				{input type="radio-btn" name="type" value="install" label="Ajouter un autre plan comptable officiel"}
				{input type="radio-btn" name="type" value="copy" label="Recopier un plan comptable pour le modifier"}
				{input type="radio-btn" name="type" value="import" label="Importer un plan comptable personnel" help="À partir d'un tableau (CSV, Office, etc.)"}
			</dl>
		</fieldset>

		<fieldset class="type-copy hidden">
			<legend>Créer un nouveau plan comptable à partir d'un existant</legend>
			<dl>
				{input type="select_groups" name="copy" options=$charts_grouped label="Recopier depuis" required=1 default=$from}
				{input type="text" name="label" label="Libellé" required=1}
				{include file="./_country_input.tpl"}
		</fieldset>

		<fieldset class="type-install hidden">
			<legend>Ajouter un nouveau plan comptable officiel</legend>
			<dl>
				{input type="select" name="install" label="Plan comptable" required=true options=$install_list}
			</dl>
		</fieldset>

		<fieldset class="type-import hidden">
			<legend>Importer un plan comptable personnel</legend>
			<dl>
				{input type="text" name="label" label="Libellé" required=1}
				{include file="./_country_input.tpl" name="import_country"}
				{input type="file" name="file" label="Fichier à importer" accept="csv" required=1}
				<dd class="help"> {* FIXME utiliser _csv_help.tpl ici ! *}
					Règles à suivre pour créer le fichier&nbsp;:
					<ul>
						<li>Le fichier doit comporter les colonnes suivantes : <em>{$columns}</em></li>
						<li>Suggestion : pour obtenir un exemple du format attendu, faire un export d'un plan comptable existant</li>
					</ul>
				</dd>
			</dl>
		</fieldset>

		<p class="submit type-all">
			{csrf_field key=$csrf_key}
			{button type="submit" name="new" label="Créer" shape="right" class="main"}
		</p>
	</form>
	<script type="text/javascript">
	{literal}
	function toggleFormOption() {
		var v = $('input[name="type"]:checked');

		if (!v.length) {
			return;
		}

		v = v[0].value;

		g.toggle('.type-import, .type-copy, .type-install', false);
		g.toggle('.type-' + v, true);
		g.toggle('.type-all', true);
	}

	$('input[name="type"]').forEach((e) => {
		e.onchange = toggleFormOption;
	});

	toggleFormOption();
	{/literal}
	</script>
{/if}

{include file="_foot.tpl"}