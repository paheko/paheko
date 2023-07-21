{include file="_head.tpl" title="Nouveau compte" current="acc/years"}

{include file="acc/charts/accounts/_nav.tpl" current="new"}

{form_errors}

{if !isset($account->type)}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Créer un nouveau compte</legend>
		<dl>
		{foreach from=$types_create item="t" key="v"}
			{input type="radio-btn" name="type" value=$v label=$t.label help=$t.help}
		{/foreach}
		</dl>
	</fieldset>
	<p class="submit">
		<input type="hidden" name="id" value="{$chart.id}" />
		{button type="submit" label="Continuer" shape="right" class="main"}
	</p>
</form>

{elseif $ask && $ask->isListedAsFavourite()}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Créer un sous-compte&nbsp;?</legend>

		<div class="help block">
			<p>Vous avez sélectionné le compte suivant&nbsp;:</p>

			<h2>{$ask.code} — {$ask.label}</h2>
		</div>

		<p class="help">
			Ce compte fait déjà partie de la liste des comptes favoris.
			Vous pouvez créer un sous-compte pour détailler les écritures, si besoin.
		</p>

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" shape="right" name="from" value=$ask.id label="Créer un sous-compte" class="main"}
		</p>
	</fieldset>

</form>

{elseif $ask}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Marquer comme favori, ou créer un sous-compte&nbsp;?</legend>

		<div class="help block">
			<p>Vous avez sélectionné le compte suivant&nbsp;:</p>

			<h2>{$ask.code} — {$ask.label}</h2>
		</div>

		<p class="help">
			Si ce compte vous convient tel quel, vous pouvez l'ajouter à vos comptes favoris, il apparaîtra ainsi toujours dans les listes de comptes.<br />
			Sinon vous pouvez créer un sous-compte pour plus de détails.
		</p>

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" shape="star" name="toggle_bookmark" value=$ask.id label="Ajouter ce compte à mes favoris" class="main"}
			— ou —
			{button type="submit" shape="right" name="from" value=$ask.id label="Créer un sous-compte" class="main"}
		</p>
	</fieldset>

</form>


{elseif !empty($missing)}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Comptes disponibles</legend>

		<p class="submit actions right">{button type="submit" shape="right" name="from" value="" label="Aucun compte ne correspond" class="main"}</p>

		<h2>Est-ce que le compte dont vous avez besoin est dans cette liste&nbsp;?</h2>

		<p class="help">
			Il est important de respecter le plan comptable&nbsp;:
			pour cela il faut choisir le compte correspondant au besoin.<br />
			Si nécessaire, il sera possible de créer un sous-compte plus précis à l'étape suivante.
		</p>

		<table class="list">
			<tbody>
			{foreach from=$missing item="item"}
				<tr class="account account-level-{$item.level}">
					<td>{if $item.already_listed}{icon shape="star" title="Ce compte est déjà favori"}{/if}</td>
					<td class="num">{$item.code}</td>
					<th>{linkbutton href="?id=%d&type=%d&ask=%d&%s"|args:$account.id_chart,$account.type,$item.id,$types_arg label=$item.label}
						{if $item.description}<span class="help">{$item.description|escape|nl2br}</span>{/if}
					</th>
					<td class="actions">
						{linkbutton href="?id=%d&type=%d&ask=%d&%s"|args:$account.id_chart,$account.type,$item.id,$types_arg label="Sélectionner" shape="right"}
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	</fieldset>

	<p class="submit">
		<input type="hidden" name="id" value="{$chart.id}" />
		<input type="hidden" name="type" value="{$account.type}" />
		{button type="submit" shape="right" name="from" value="" label="Aucun compte ne correspond" class="main"}
	</p>
</form>


{else}

<form method="post" action="{$self_url}" data-focus="[name='code'],[name=code_value]">

	<fieldset>
		<legend>Créer un nouveau compte</legend>
		{include file="acc/charts/accounts/_account_form.tpl" can_edit=true create=true}
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{if $from}
		<input type="hidden" name="from" value="{$from.id}" />
		{/if}
		{button type="submit" name="save" label="Créer" shape="right" class="main"}
	</p>

</form>

{/if}

{include file="_foot.tpl"}