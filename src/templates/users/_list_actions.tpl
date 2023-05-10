		<tfoot>
			<tr>
				<td class="check"><input type="checkbox" value="Tout cocher / décocher" id="f_all2" /><label for="f_all2"></label></td>
				<td class="actions" colspan="{$colspan}">
					<em>Pour les membres cochés :</em>
					{csrf_field key="membres_action"}
					<select name="action">
						<option value="">— Choisir une action à effectuer —</option>
						<option value="move">Changer de catégorie</option>
						<option value="template">Générer des documents</option>
						{if empty($hide_delete)}
							<option value="delete">Supprimer le membre</option>
						{/if}
						{if !isset($export) || $export != false}
						<optgroup label="Exporter au format…">
							<option value="csv" data-no-dialog="true">CSV</option>
							<option value="ods" data-no-dialog="true">LibreOffice</option>
							{if CALC_CONVERT_COMMAND}
								<option value="xlsx" data-no-dialog="true">Excel</option>
							{/if}
						</optgroup>
						{/if}
					</select>
					<noscript>
						<input type="submit" value="OK" />
					</noscript>
				</td>
			</tr>
		</tfoot>