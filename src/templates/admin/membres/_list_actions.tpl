		<tfoot>
			<tr>
				{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}<td class="check"><input type="checkbox" value="Tout cocher / décocher" id="f_all2" /><label for="f_all2"></label></td>{/if}
				<td class="actions" colspan="{$colspan}">
					<em>Pour les membres cochés :</em>
					{csrf_field key="membres_action"}
					<select name="action">
						<option value="">— Choisir une action à effectuer —</option>
						<option value="move">Changer de catégorie</option>
						{if !isset($export) || $export != false}
						<option value="csv">Exporter en tableau CSV</option>
						<option value="ods">Exporter en classeur Office</option>
						{/if}
						<option value="delete">Supprimer</option>
					</select>
					<noscript>
						<input type="submit" value="OK" />
					</noscript>
				</td>
			</tr>
		</tfoot>