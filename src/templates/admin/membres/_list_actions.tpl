		<tfoot>
			<tr>
				{if $session->canAccess('membres', Membres::DROIT_ADMIN)}<td class="check"><input type="checkbox" value="Tout cocher / décocher" /></td>{/if}
				<td class="actions" colspan="{$colspan}">
					<em>Pour les membres cochés :</em>
					{csrf_field key="membres_action"}
					<select name="action">
						<option value="">— Choisir une action à effectuer —</option>
						<option value="move">Changer de catégorie</option>
						<option value="csv">Exporter en tableau CSV</option>
						<option value="ods">Exporter en classeur ODS</option>
						<option value="delete">Supprimer</option>
					</select>
					<noscript>
						<input type="submit" value="OK" />
					</noscript>
				</td>
			</tr>
		</tfoot>