{if $create}
<input type="hidden" name="type" value="{$account.type}" />
{/if}

<dl>
	{if $can_edit}
		{if !$account.type && !$account->exists()}
			<dt><label for="f_position_0">Position au bilan ou résultat</label> <b>(obligatoire)</b></dt>
			<dd class="help">La position permet d'indiquer dans quelle partie du bilan ou du résultat doit figurer le compte.</dd>
			{input type="radio" label="Ne pas utiliser ce compte au bilan ni au résultat" name="position" value=0 source=$account}
			{input type="radio" label="Bilan : actif" name="position" value=Entities\Accounting\Account::ASSET source=$account help="ce que possède l'association : stocks, locaux, soldes bancaires, etc."}
			{input type="radio" label="Bilan : passif" name="position" value=Entities\Accounting\Account::LIABILITY source=$account help="ce que l'association doit : dettes, provisions, réserves, etc."}
			{input type="radio" label="Bilan : actif ou passif" name="position" value=Entities\Accounting\Account::ASSET_OR_LIABILITY source=$account help="le compte sera placé à l'actif si son solde est débiteur, ou au passif s'il est créditeur"}
			{input type="radio" label="Résultat : charge" name="position" value=Entities\Accounting\Account::EXPENSE source=$account help="dépenses"}
			{input type="radio" label="Résultat : produit" name="position" value=Entities\Accounting\Account::REVENUE source=$account help="recettes"}
		{elseif $account->canSetAssetOrLiabilityPosition()}
			<dt><label for="f_position_0">Position au bilan</label> <b>(obligatoire)</b></dt>
			<dd class="help">La position permet d'indiquer dans quelle partie du bilan doit figurer le compte.</dd>
			{input type="radio" label="Bilan : actif" name="position" value=Entities\Accounting\Account::ASSET source=$account help="ce que possède l'association : stocks, locaux, soldes bancaires, etc."}
			{input type="radio" label="Bilan : passif" name="position" value=Entities\Accounting\Account::LIABILITY source=$account help="ce que l'association doit : dettes, provisions, réserves, etc."}
			{input type="radio" label="Bilan : actif ou passif" name="position" value=Entities\Accounting\Account::ASSET_OR_LIABILITY source=$account help="le compte sera placé à l'actif si son solde est débiteur, ou au passif s'il est créditeur"}
		{elseif $account->exists()}
			<dt>Position du compte</dt>
			<dd>
				{if $account.position == $account::EXPENSE || $account.position == $account::REVENUE}Au compte de résultat{else}Au bilan{/if}
				—
				{$account->position_name()}
			</dd>
		{/if}

		{if $account.type}
			<dt><label for="f_code">Numéro de compte</label>  <b>(obligatoire)</b></dt>
			<dd>
				{input type="text" readonly=true name="code_base" default=$code_base size=$code_base|strlen}
				{input type="text" maxlength="15" size="15" pattern="[A-Z0-9]+" name="code_value" required=true default=$code_value}
			</dd>
			<dd class="help">Le numéro du compte sert à trier le compte dans le plan comptable, et à retrouver le compte plus rapidement.</dd>
		{else}
			{input type="text" label="Numéro" maxlength="20" pattern="[A-Z0-9]+" name="code" source=$account required=true help="Le numéro du compte sert à trier le compte dans le plan comptable, attention à choisir un numéro qui correspond au plan comptable."}
		{/if}
		<dd class="help">Le numéro ne peut contenir que des chiffres et des lettres majuscules.</dd>
		{input type="text" label="Libellé" name="label" source=$account required=true}
	{else}
		<dt>Position du compte</dt>
		<dd>
			{if $account.position == $account::EXPENSE || $account.position == $account::REVENUE}Au compte de résultat{else}Au bilan{/if}
			—
			{$account->position_name()}
		</dd>
		<dt>Type</dt>
		<dd>{$account->type_name()}</dd>
		<dd class="help">Le type est déterminé selon le numéro du compte.</dd>
		{input type="text" disabled=true name="code" source=$account label="Numéro de compte"}
		{input type="text" label="Libellé" name="label" source=$account disabled=true}
	{/if}

	{input type="textarea" label="Description" name="description" source=$account}
	{input type="checkbox" label="Compte favori" name="bookmark" source=$account value=1 help="Si coché, le compte apparaîtra en priorité dans les listes de comptes"}

	{if !$account->exists() && in_array($account.type, [$account::TYPE_BANK, $account::TYPE_CASH, $account::TYPE_OUTSTANDING, $account::TYPE_THIRD_PARTY]) && !empty($current_year)}
		{input type="money" name="opening_amount" label="Solde d'ouverture" help="Si renseigné, ce solde sera inscrit dans l'exercice « %s »."|args:$current_year.label}
	{/if}
</dl>
