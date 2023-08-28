{include file="_head.tpl" title="Importer des membres" current="users"}

{include file="users/_nav.tpl" current="import"}

{form_errors}

{if $_GET.msg == 'OK'}
	<p class="block confirm">
		L'import s'est bien déroulé.
	</p>
{/if}

<form method="post" action="{$self_url}" enctype="multipart/form-data">

{if $csv->ready()}
	{if $report.has_logged_user}
	<p class="alert block">
		Ce fichier comporte une modification de votre profil de membre.
		Celle-ci a été ignorée afin d'empêcher que vous ne puissiez plus vous connecter.<br />
		Pour modifier vos informations de membre, utilisez la page {linkbutton shape="user" label="Mes informations personnelles" href="!me/"} ou demandez à un autre administrateur de modifier votre fiche.
	</p>
	{/if}

	<p class="help block">
		Aucun problème n'a été détecté.<br />
		Voici un résumé des changements qui seront apportés par cet import&nbsp;:
	</p>

	{if count($report.created)}
	<details>
		<summary>
			<h2>{{%n membre sera créé}{%n membres seront créés} n=$report.created|count}</h2>
		</summary>
		<p class="help">Les membres suivants mentionnés dans le fichier seront ajoutés.</p>
		{include file="users/_import_list.tpl" list=$report.created}
	</details>
	{/if}

	{if count($report.modified)}
	<details>
		<summary>
			<h2>{{%n membre sera modifié}{%n membres seront modifiés} n=$report.modified|count}</h2>
		</summary>
		<p class="help">Les membres suivants mentionnés dans le fichier seront modifiés.<br />
			En rouge ce qui sera supprimé, en vert ce qui sera ajouté.</p>
		{include file="users/_import_list.tpl" list=$report.modified}
	</details>
	{/if}

	{if count($report.unchanged)}
		<h3>{{%n membre ne sera pas modifié}{%n membres ne seront pas modifiés} n=$report.unchanged|count}</h3>
	{/if}

	{if !count($report.modified) && !count($report.created)}
	<p class="error block">
		Aucune modification ne serait apportée par ce fichier à importer. Il n'est donc pas possible de terminer l'import.
	</p>
	{else}
	<p class="help">
		En validant ce formulaire, ces changements seront appliqués.
	</p>
	{/if}

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="cancel" value="1" label="Annuler" shape="left"}
		{if count($report.modified) || count($report.created)}
		{button type="submit" name="import" label="Importer" class="main" shape="right"}
		{/if}
	</p>
{elseif $csv->loaded()}

	{include file="common/_csv_match_columns.tpl"}

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="cancel" value="1" label="Annuler" shape="left"}
		{button type="submit" name="preview" label="Prévisualiser" shape="right" class="main"}
	</p>

{else}

	<fieldset>
		<legend>Importer depuis un fichier</legend>
		<dl>
			{input type="file" name="file" label="Fichier à importer" required=true accept="csv"}
			{include file="common/_csv_help.tpl" csv=$csv}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Configuration de l'import</legend>
		<dl>
			<dt><label for="f_mode">Mode d'import</label> <b>(obligatoire)</b></dt>

			{input type="radio" name="mode" value="create" label="Créer tous les membres" required=true}
			<dd class="help">Tous les membres trouvés dans le fichier seront créés.<br />Cela peut amener à avoir des membres en doublon si on réalise plusieurs imports du même fichier.</dd>

			{input type="radio" name="mode" value="update" label="Mettre à jour en utilisant le numéro de membre" required=true}
			<dd class="help">
				Les membres présents dans le fichier qui mentionnent un numéro de membre seront mis à jour en utilisant ce numéro.<br/>
				Si une ligne du fichier mentionne un numéro de membre qui n'existe pas ou n'a pas de numéro de membre, l'import échouera.
			</dd>

			{input type="radio" name="mode" value="auto" label="Automatique : créer ou mettre à jour en utilisant le numéro de membre" required=true}
			<dd class="help">
				Met à jour la fiche d'un membre si son numéro existe, sinon crée un membre si le numéro de membre indiqué n'existe pas ou n'est pas renseigné.
			</dd>

		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="load" label="Charger le fichier" shape="right" class="main"}
	</p>
{/if}


</form>

{include file="_foot.tpl"}