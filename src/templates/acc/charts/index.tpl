{include file="admin/_head.tpl" title="Gestion des plans comptables" current="acc/charts"}

<nav class="tabs">
    <ul>
        <li><a href="{$admin_url}acc/accounts/">Comptes favoris</a></li>
        <li><a href="{$admin_url}acc/accounts/all.php">Tous les comptes</a></li>
        <li class="current"><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
        <li><a href="{$admin_url}acc/charts/import.php">Importer un plan comptable</a></li>
    </ul>
</nav>

{if count($list)}
    <table class="list">
        <thead>
        	<td>Pays</td>
            <th>Libellé</th>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$list item="item"}
                <tr>
                	<td>{$item.country|get_country_name}</td>
                    <th><a href="{$admin_url}acc/accounts/all.php?id={$item.id}">{$item.label}</a> <em>{if $item.code}(officiel){else}(copie){/if}</em></th>
                    <td class="actions">
                        {icon shape="menu" label="Gérer les comptes" href="acc/accounts/all.php?id=%d"|args:$item.id}
                        {icon shape="edit" label="Renommer" href="acc/charts/edit.php?id=%d"|args:$item.id}
                        {icon shape="export" label="Exporter en CSV" href="acc/charts/export.php?id=%d"|args:$item.id}
                        {if empty($item.code)}
                            {icon shape="upload" label="Importer" href="acc/charts/import.php?id=%d"|args:$item.id}
                            {icon shape="delete" label="Supprimer" href="acc/charts/delete.php?id=%d"|args:$item.id}
                        {else}
                            {icon shape="reset" label="Remettre à zéro" href="acc/charts/reset.php?id=%d"|args:$item.id}
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{/if}

<form method="post" action="{$self_url_no_qs}">
	<fieldset>
		<legend>Créer un nouveau plan comptable</legend>
		<dl>
			{input type="select_groups" name="plan" options=$charts_groupped label="Recopier depuis" required=1}
			{input type="text" name="label" label="Libellé" required=1}
			{input type="select" name="country" label="Pays" required=1 options=$country_list default=$config.pays}
		</dl>
		<p class="submit">
			<input type="submit" value="Créer &rarr;" />
		</p>
	</fieldset>
</form>

{include file="admin/_foot.tpl"}