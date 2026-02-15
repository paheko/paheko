{include file="_head.tpl" title="Règle d'import bancaire"}

{form_errors}

<form method="post" action="">
	<fieldset>
		<legend>Propriétés</legend>
		<dl>
			{input type="text" name="label" label="Nom de la règle" source=$rule}
		</dl>
	</fieldset>
	<fieldset>
		<legend>Appliquer cette règle si…</legend>
		<p class="help">Laisser vide un critère pour ne pas l'utiliser.</p>
		<dl>
			{input type="text" name="match_file_name" label="Le nom du fichier importé contient…" source=$rule}
			<dd class="help">Exemple : indiquer <samp>historiqueoperations_</samp> restreindra cette règle aux imports utilisant un fichier qui comporte ce mot dans son nom.</dd>
			{input type="text" name="match_account" label="Le numéro du compte utilisé pour l'import contient…" source=$rule size=8}
			<dd class="help">Exemple : indiquer <samp>5121</samp> (avec l'option RegExp) restreindra cette règle aux imports sur les comptes dont le numéro commence par 5121.</dd>
			{input type="text" name="match_label" label="Le libellé de l'opération contient…" source=$rule}
			<dd class="help">Exemple : indiquer <samp>VIR SALAIRE</samp> restreindra cette règle aux opérations qui contiennent cette expression, qu'elle soit en majuscules ou en minuscules.</dd>
			{input type="text" name="match_date" label="Le date de l'opération contient…" source=$rule help="(format JJ/MM/AAAA)" size=12}
			<dd class="help">Exemple : indiquer <samp>22/</samp> restreindra cette règle aux opérations qui sont le 22ème jour du mois.</dd>
			{input type="money" name="min_amount" label="Le montant est supérieur ou égal à…" source=$rule}
			{input type="money" name="max_amount" label="Le montant est inférieur ou égal à…" source=$rule}
			{input type="checkbox" name="regexp" value=1 label="Activer les expressions régulières" source=$rule}
			<dd class="help">
				Si coché, il sera possible d'utiliser des RegExp dans la recherche de libellé, de date, de compte et de nom de fichier.<br />
				{linkbutton shape="help" label="Tutoriel : les expressions régulières" href="https://zestedesavoir.com/tutoriels/3651/les-expressions-regulieres-1/" target="_blank"}
			</dd>
		</dl>
		<details class="block help">
			<summary><h4>Usage avancé des expressions régulières</h4></summary>
			<p>
				Les captures peuvent être utilisées dans le champ correspondant de l'écriture.<br />
				Exemple : <code>VIR (.+)</code> permet d'inscrire <code>Virement $1</code> pour le libellé.
			</p>
			<p>
				Les groupes nommés peuvent être utilisés dans n'importe quel champ.<br />
				Exemple : indiquer <code>VIR (?P&lt;ref&gt;.+)</code> pour le libellé de l'opération permet d'inscrire <code>$ref</code> pour la référence de paiement.
			</p>
		</details>
	</fieldset>
	<fieldset>
		<legend>Écriture qui sera créée</legend>
		<dl>
			{input type="text" name="target_account" label="Numéro du compte destinataire" source=$rule size=8}
			{input type="text" name="new_label" label="Libellé" source=$rule}
			{input type="text" name="new_reference" label="Numéro de pièce comptable" source=$rule}
			{input type="text" name="new_payment_ref" label="Référence de paiement" source=$rule}
		</dl>
	</fieldset>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>
</form>

{include file="_foot.tpl"}