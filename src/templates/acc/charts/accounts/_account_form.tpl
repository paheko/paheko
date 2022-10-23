<dl>
	{if !$account.type || !$create}
		{input type="select" label="Type de compte usuel" name="type" source=$account required=true options=$types}
		<dd class="help">Le statut de compte usuel est utilisé pour les écritures <em>«&nbsp;simplifiées&nbsp;»</em> (recettes, dépenses, dettes, créances, virements), pour la liste des comptes, et également pour proposer certaines fonctionnalités (rapprochement pour les comptes bancaires, règlement rapide de dette et créance, dépôt de chèques).</dd>
		<dd class="help">Un compte qui n'a pas de type usuel ne pourra être utilisé que dans une saisie avancée, et ne sera visible que dans les rapports de l'exercice.</dd>
	{else}
	<dt>Type de compte</dt>
	<dd>
		<?php $t = $types[$account->type]; ?> {$t}
		<input type="hidden" name="type" value="{$account.type}" />
	</dd>
	{/if}

	{if !$account.type || $account.type == $account::TYPE_VOLUNTEERING}
		<dt><label for="f_position_0">Position au bilan ou résultat</label>{if !$edit_disabled} <b>(obligatoire)</b>{/if}</dt>
		<dd class="help">La position permet d'indiquer dans quelle partie du bilan ou du résultat doit figurer le compte.</dd>
		{input type="radio" label="Ne pas utiliser ce compte au bilan ni au résultat" name="position" value=0 source=$account disabled=$edit_disabled}
		{if $account.type != $account::TYPE_VOLUNTEERING}
		{input type="radio" label="Bilan : actif" name="position" value=Entities\Accounting\Account::ASSET source=$account help="ce que possède l'association : stocks, locaux, soldes bancaires, etc." disabled=$edit_disabled}
		{input type="radio" label="Bilan : passif" name="position" value=Entities\Accounting\Account::LIABILITY source=$account help="ce que l'association doit : dettes, provisions, réserves, etc." disabled=$edit_disabled}
		{input type="radio" label="Bilan : actif ou passif" name="position" value=Entities\Accounting\Account::ASSET_OR_LIABILITY source=$account help="le compte sera placé à l'actif si son solde est débiteur, ou au passif s'il est créditeur" disabled=$edit_disabled}
		{/if}
		{input type="radio" label="Résultat : charge" name="position" value=Entities\Accounting\Account::EXPENSE source=$account help="dépenses" disabled=$edit_disabled}
		{input type="radio" label="Résultat : produit" name="position" value=Entities\Accounting\Account::REVENUE source=$account help="recettes" disabled=$edit_disabled}
	{/if}

	{input type="text" label="Numéro" maxlength="20" pattern="[A-Z0-9]+" name="code" source=$account required=true help="Le numéro du compte sert à trier le compte dans le plan comptable, attention à choisir un numéro qui correspond au plan comptable." disabled=$edit_disabled}
	<dd class="help">Le numéro ne doit contenir que des chiffres et des lettres majuscules.</dd>
	{input type="text" label="Libellé" name="label" source=$account required=true disabled=$edit_disabled}
	{input type="textarea" label="Description" name="description" source=$account}

	{if $create && in_array($account.type, [$account::TYPE_BANK, $account::TYPE_CASH, $account::TYPE_OUTSTANDING, $account::TYPE_THIRD_PARTY]) && !empty($current_year)}
		{input type="money" name="opening_amount" label="Solde d'ouverture" help="Si renseigné, ce solde sera inscrit dans l'exercice « %s »."|args:$current_year.label}
	{/if}
</dl>
