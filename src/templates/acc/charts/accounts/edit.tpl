{include file="admin/_head.tpl" title="Modifier un compte" current="acc/charts" js=1}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Modifier un compte</legend>
		<dl>
			{input type="select" label="Type de compte favori" name="type" source=$account required=true options=$types}
			<dd class="help">Le statut de compte favori est utilisé pour les écritures <em>«&nbsp;simplifiées&nbsp;»</em> (recettes, dépenses, dettes, créances, virements), pour la liste des comptes, et également pour proposer certaines fonctionnalités (rapprochement pour les comptes bancaires, règlement rapide de dette et créance, dépôt de chèques).</dd>
			<dd class="help">Un compte qui n'a pas de type favori ne pourra être utilisé que dans une saisie avancée, et ne sera visible que dans les rapports de l'exercice.</dd>

			<dt><label for="f_position_0">Position au bilan ou résultat</label> <b>(obligatoire)</b></dt>
			<dd class="help">La position permet d'indiquer dans quelle partie du bilan ou du résultat doit figurer le compte.</dd>
			<dd class="help">Les comptes inscrits en actif ou passif figureront dans le bilan, alors que ceux inscrits en produit ou charge figureront au compte de résultat.</dd>
			{input type="radio" label="Ne pas utiliser ce compte au bilan ni au résultat" name="position" value=0 source=$account}
			{input type="radio" label="Bilan : actif" name="position" value=1 source=$account help="ce que possède l'association : stocks, locaux, soldes bancaires, etc."}
			{input type="radio" label="Bilan : passif" name="position" value=2 source=$account help="ce que l'association doit : dettes, provisions, réserves, etc."}
			{input type="radio" label="Bilan : actif ou passif" name="position" value=3 source=$account help="le compte sera placé à l'actif si son solde est débiteur, ou au passif s'il est créditeur"}
			{input type="radio" label="Résultat : produit" name="position" value=4 source=$account help="recettes"}
			{input type="radio" label="Résultat : charge" name="position" value=5 source=$account help="dépenses"}

			{input type="text" label="Code" maxlength="10" name="code" source=$account required=true help="Le code du compte sert à trier le compte dans le plan comptable, attention à choisir un code qui correspond au plan comptable."}
			{input type="text" label="Libellé" name="label" source=$account required=true}
			{input type="textarea" label="Description" name="description" source=$account required=true}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_accounts_edit_%s"|args:$account.id}
		<input type="submit" name="edit" value="Enregistrer &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}