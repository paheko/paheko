<?php
assert(isset($create) && is_bool($create));
assert(isset($has_past_services) && is_bool($has_past_services));
assert(isset($current_only) && is_bool($current_only));
assert(isset($form_url) && is_string($form_url));
assert(isset($today) && $today instanceof \DateTimeInterface);
assert($create === false || isset($account_types));
assert(isset($grouped_services) && is_array($grouped_services));
?>

<form method="post" action="{$self_url}" data-focus="1" data-create="{$create|escape:json}">

	<fieldset>
		<legend>Inscrire à une activité</legend>

		<dl>
		{if $create && $users}
			<dt>
				Membres à inscrire
			</dt>
			<dd>
				<details>
					<summary>{{%n membre sélectionné.}{%n membres sélectionnés.} n=$users|count}</summary>
					<table>
						{foreach from=$users key="id" item="name"}
						<tr>
							<td>
								<input type="hidden" name="users[{$id}]" value="{$name}" />
								{if !empty($allow_users_edit)}
								{button shape="delete" onclick="this.parentNode.parentNode.remove();" title="Supprimer de la liste"}
								{/if}
							</td>
							<th>
								{$name}
							</th>
						</tr>
						{/foreach}
					</table>
				</details>
			</dd>
		{elseif $create && $copy_service}
			<dt>Recopier depuis l'activité</dt>
			<dd><strong>{$copy_service.label}</strong><input type="hidden" name="copy" value="s{$copy_service.id}" /></dd>
			<dd><em>{if $copy_only_paid}(seulement les inscriptions marquées comme payées){else}(toutes les inscriptions){/if}</em><input type="hidden" name="copy_only_paid" value="{$copy_service_only_paid}" /></dd>
		{elseif $create && $copy_fee}
			<dt>Recopier depuis le tarif</dt>
			<dd><strong>{$copy_fee->service()->label} — {$copy_fee.label}</strong><input type="hidden" name="copy" value="f{$copy_fee.id}" /></dd>
			<dd><em>{if $copy_only_paid}(seulement les inscriptions marquées comme payées){else}(toutes les inscriptions){/if}</em><input type="hidden" name="copy_only_paid" value="{$copy_service_only_paid}" /></dd>
		{/if}

			<dt><label for="f_service_ID">Activité</label> <b>(obligatoire)</b></dt>

			{if $has_past_services}
			<dd>
				{* We can't use a button type="submit" here because it would trigger when user presses Enter, instead of the true submit button *}
				<input type="hidden" name="past_services" value="{$current_only}" />
				{if $current_only}
					Seules les activités courantes sont affichées.
					{button value="1" shape="reset" type="button" onclick="this.form.past_services=this.value; this.form.submit();" label="Inscrire à une activité passée"}
				{else}
					Seules les activités passées sont affichées.
					{button value="0" shape="left" type="button"  onclick="this.form.past_services=this.value; this.form.submit();" label="Inscrire à une activité courante"}
				{/if}
			</dd>
			{/if}


			{foreach from=$grouped_services item="service"}
				<dd class="radio-btn">
					{input type="radio" name="id_service" value=$service.id data-duration=$service.duration data-expiry=$service.expiry_date|date_short label=null source=$service_user}
					<label for="f_id_service_{$service.id}">
						<div>
							<h3>{$service.label}</h3>
							<p>
								{if $service.duration}
									{$service.duration} jours
								{elseif $service.start_date}
									du {$service.start_date|date_short} au {$service.end_date|date_short}
								{else}
									ponctuelle
								{/if}
							</p>
							{if $service.description}
							<p class="help">
								{$service.description|escape|nl2br}
							</p>
							{/if}
						</div>
					</label>
				</dd>
			{foreachelse}
				<dd><p class="error block">Aucune activité trouvée</p></dd>
			{/foreach}

		</dl>

		{foreach from=$grouped_services item="service"}
		<?php if (!count($service->fees)) { continue; } ?>
		<dl data-service="s{$service.id}">
			<dt><label for="f_fee">Tarif</label> <b>(obligatoire)</b></dt>
			{foreach from=$service.fees key="service_id" item="fee"}
			<dd class="radio-btn">
				{input type="radio" name="id_fee" value=$fee.id data-user-amount=$fee.user_amount data-account=$fee.id_account data-year=$fee.id_year label=null data-project=$fee.id_project source=$service_user }
				<label for="f_id_fee_{$fee.id}">
					<div>
						<h3>{$fee.label}</h3>
						<p>
							{if $fee.formula !== null && isset($fee.user_amount)}
								<strong>{$fee.user_amount|raw|money_currency:false}</strong> (montant calculé)
							{elseif $fee.formula}
								montant calculé, variable selon les membres
							{elseif $fee.user_amount}
								<strong>{$fee.user_amount|raw|money_currency}</strong>
							{else}
								prix libre ou gratuit
							{/if}
						</p>
						{if $fee.description}
						<p class="help">
							{$fee.description|escape|nl2br}
						</p>
						{/if}
					</div>
				</label>
			</dd>
			{/foreach}
		</dl>
		{/foreach}

	</fieldset>


	</fieldset>

	<fieldset>
		<legend>Détails</legend>
		<dl>
			{input type="date" name="date" required=1 default=$today source=$service_user label="Date d'inscription"}
			{input type="date" name="expiry_date" source=$service_user label="Date d'expiration de l'inscription"}
			{input type="checkbox" name="paid" value="1" source=$service_user default="1" label="Marquer cette inscription comme payée"}
			<dd class="help">Décocher cette case pour pouvoir suivre les règlements de personnes qui payent en plusieurs fois. Il sera possible de cocher cette case lorsque le solde aura été réglé.</dd>
		</dl>
	</fieldset>

	{if $create}
	<fieldset class="accounting">
		<legend>{input type="checkbox" name="create_payment" value=1 default=1 label="Enregistrer en comptabilité"}</legend>

		<dl>
		{if !empty($users)}
		<dd class="help">Une écriture sera créée pour chaque membre inscrit.</dd>
		{/if}

			{input type="money" name="amount" label="Montant réglé par le membre" required=true help="En cas de règlement en plusieurs fois il sera possible d'ajouter des règlements via la page de suivi des activités de ce membre."}
			{input type="list" target="!acc/charts/accounts/selector.php?types=%s"|args:$account_types name="account_selector" label="Compte de règlement" required=true}
			{input type="text" name="reference" label="Numéro de pièce comptable" help="Numéro de facture, de reçu, de note de frais, etc."}
			{input type="text" name="payment_reference" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc."}
			{input type="textarea" name="notes" label="Remarques"}
			{if count($projects) > 0}
				{input type="select" options=$projects name="id_project" label="Projet analytique" required=false default_empty="— Aucun —"}
			{/if}
		</dl>
	</fieldset>
	{/if}

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>