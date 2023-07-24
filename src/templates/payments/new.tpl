{include file="_head.tpl" title="Paiements" current="payments"}

{include file="payments/_menu.tpl"}

<h2 class="ruler">Créer un paiement</h2>

<form method="POST" action="{$self_url}">
	<fieldset>
		<legend>Paiement</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=true}
			{input type="date" name="date" label="Date" required=false}
			{input type="select" name="type" label="Type" options=Entities\Payments\Payment::TYPES default=Entities\Payments\Payment::UNIQUE_TYPE required=true}
			{input type="select" name="method" label="Méthode" options=Entities\Payments\Payment::METHODS required=true}
			{input type="select" name="provider" label="Prestataire" options=$provider_options default=Payments\Providers::MANUAL_PROVIDER required=true}
			{input type="select" name="status" label="Statut" options=$status_options default=Entities\Payments\Payment::VALIDATED_STATUS required=true}
			{input type="list" name="payer" label="Payeur/euse" target="!users/selector.php" can_delete="true" required=true}

			<dt><label for="user_list">Membres concerné·e·s</label> <i>(facultatif)</i></dt>
			<dd class="help">Ex : bénéficiaires d'une contre-partie</dd>
			<dd>
				<table id="user_list" class="list">
					<thead>
						<tr>
							<th>Membre</th>
							<th>Remarque</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>{input type="list" name="users[0]" label="" target="!users/selector.php" multiple=false can_delete=false required=false help="Ex : bénéficiaires d'une contre-partie"}</td>
							<td>{input type="text" name="user_notes[0]" label="" required=false placeholder="Ex : adhésion demi-tarif"}</td>
							<td>{button name="item_0_delete_button" label="Enlever" shape="minus" onclick="remove_item(0)"}</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="2"></td>
							<td>{button name="item_add_button" label="Ajouter" shape="plus" onclick="add_item()"}</td>
						</tr>
					</tfoot>
				</table>
			</dd>
			
			{input type="text" name="reference" label="Référence"}
			{input type="money" name="amount" label="Montant" required=true}

			<dt><strong>Comptabilité</strong></dt>
			{input name="accounting" type="checkbox" value="1" label="Enregistrer en comptabilité" default=false}
			<dd class="help">Laissez cette case décochée si vous n'utilisez pas Paheko pour la comptabilité.</dd>
		</dl>
	</fieldset>

	<fieldset class="accounting">
		<legend>Enregistrer en comptabilité</legend>
		{if !count($years)}
			<p class="error block">Il n'y a aucun exercice ouvert dans la comptabilité, il n'est donc pas possible d'enregistrer les activités dans la comptabilité. Merci de commencer par {link href="!acc/years/new.php" label="créer un exercice"}.</p>
		{else}
		<dl>
			<dt><label for="f_id_year">Exercice</label> <b>(obligatoire)</b></dt>
			<dd>
				<select id="f_id_year" name="id_year">
					<option value="">-- Sélectionner un exercice</option>
					{foreach from=$years item="year"}
					<option value="{$year.id}">{$year.label} — {$year.start_date|date_short} au {$year.end_date|date_short}</option>
					{/foreach}
				</select>
			</dd>
			{input type="list" target="!acc/charts/accounts/selector.php?targets=%s"|args:'6' name="credit" label="Type de recette" required=1}
			{input type="list" target="!acc/charts/accounts/selector.php?targets=%s"|args:'1:2:3' name="debit" label="Compte d'encaissement" required=1}
			{input type="textarea" name="notes" label="Remarques" rows="4" cols="30"}
		</dl>
		{/if}
	</fieldset>

	{csrf_field key=$csrf_key}
	{button type="submit" name="save" label="Créer" class="main"}
</form>

<script type="text/javascript" src="{$admin_url}static/scripts/payment.js?{$version_hash}"></script>

{include file="_foot.tpl"}