<dl>

	<dt><label for="f_service_ID">Activité</label> <b>(obligatoire)</b></dt>

	{foreach from=$grouped_services item="service"}
		<dd class="radio-btn">
			{input type="radio" name="id_service" value=$service.id data-duration=$service.duration data-expiry=$service.expiry_date|date_short label=null}
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
		{input type="radio" name="id_fee" value=$fee.id data-user-amount=$fee.user_amount data-account=$fee.id_account data-year=$fee.id_year label=null}
		<label for="f_id_fee_{$fee.id}">
			<div>
				<h3>{$fee.label}</h3>
				<p>
					{if !$fee.user_amount}
						prix libre ou gratuit
					{elseif $fee.user_amount && $fee.formula}
						<strong>{$fee.user_amount|raw|money_currency}</strong> (montant calculé)
					{elseif $fee.user_amount}
						<strong>{$fee.user_amount|raw|money_currency}</strong>
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