<fieldset>
	<legend>Importer depuis un fichier CSV générique</legend>
	<dl>
		<dd class="help">{$csv->count()} lignes trouvées dans le fichier</dd>
		<dt>{input type="checkbox" name="skip_first_line" value="1" label="Ne pas importer la première ligne" help="Décocher cette case si la première ligne ne contient pas l'intitulé des colonnes, mais des données" default=1}
		<dt><label>Correspondance des colonnes</label></dt>
		<dd>
			<table class="list auto">
				<thead>
					<tr>
						<th>Colonne du CSV à importer</th>
						<th>Importer cette colonne comme…</th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$csv->getFirstLine() key="index" item="csv_field"}
					<tr>
						<th>{$csv_field}</th>
						<td>
							<select name="translation_table[{$index}]">
							<?php $selected = $csv->getSelectedTable(); ?>
								<option value="">-- Ne pas importer cette colonne</option>
								{foreach from=$csv->getColumns() item="label" key="key"}
									<option value="{$key}" {if $selected[$index] == $key}selected="selected"{/if}>{$label}</option>
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