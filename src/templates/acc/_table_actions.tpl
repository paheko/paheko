					<em>Pour les écritures cochées :</em>
					<input type="hidden" name="from" value="{$self_url}" />
					{csrf_field key="acc_actions"}
					<select name="action" data-form-action="{"!acc/transactions/actions.php"|local_url}">
						<option value="">— Choisir une action à effectuer —</option>
						<option value="change_project">Ajouter/enlever d'un projet</option>
						{if !empty($enable_letter)}
							<option value="letter">Lettrer</option>
							<option value="unletter">Supprimer le lettrage</option>
						{/if}
						<option value="delete">Supprimer les écritures</option>
					</select>
					<noscript>
						{button type="submit" value="OK" shape="right" label="Valider"}
					</noscript>
