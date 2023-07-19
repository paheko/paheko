<fieldset>
	<legend>Importer depuis un tableau</legend>
	<dl>
		<dd class="help">{$csv->count()} lignes trouvées dans le fichier</dd>
		<dt>{input type="checkbox" name="skip_first_line" value="1" label="Ne pas importer la première ligne" help="Décocher cette case si la première ligne ne contient pas l'intitulé des colonnes, mais des données" default=1}
		<dt><label>Correspondance des colonnes</label></dt>
		<dd>
			<table class="list auto">
				<thead>
					<tr>
						<th>Colonne du fichier à importer</th>
						<td></td>
						<th>Importer cette colonne comme…</th>
					</tr>
				</thead>
				<tbody>
				<?php $selected = $csv->getSelectedTable(); ?>
				{foreach from=$csv->getFirstLine() key="index" item="csv_field"}
					<tr>
						<th>{$csv_field}</th>
						<td class="help">{icon shape="right"}</td>
						<td>
							<select name="translation_table[{$index}]">
								<option value="">-- Ne pas importer cette colonne</option>
								{foreach from=$csv->getColumnsWithDefaults() item="column"}
									<option value="{$column.key}" {if $selected[$index] == $column.key}selected="selected"{/if}>{$column.label}</option>
								{/foreach}
							</select>
						</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</dd>
	</dl>
</fieldset>