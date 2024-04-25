		<tfoot>
			<tr>
				<td class="check"><input type="checkbox" value="Tout cocher / décocher" id="f_all2" /><label for="f_all2"></label></td>
				<td class="actions" colspan="{$colspan}">
					<em>Pour les membres cochés :</em>
					{csrf_field key="membres_action"}
					<select name="action" data-form-action="{$admin_url}users/action.php" data-form-target="_dialog">
						<option value="">— Choisir une action à effectuer —</option>
						<option value="subscribe">Inscrire à une activité</option>
						{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
							<option value="move">Changer de catégorie</option>
							{if empty($hide_delete)}
								<option value="delete">Supprimer les membres</option>
								<option value="delete_files">Supprimer les fichiers du membre</option>
							{/if}
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