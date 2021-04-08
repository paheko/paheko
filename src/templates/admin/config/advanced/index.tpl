{include file="admin/_head.tpl" title="Fonctions avancées" current="config" custom_css=["config.css"]}

{include file="admin/config/_menu.tpl" current="advanced" sub_current=null}

<p class="help block">
	Attention, les fonctions avancées peuvent permettre de supprimer des données ou rendre votre instance inutilisable&nbsp;!
</p>

{form_errors}

{if $_GET.msg == 'RESET'}
	<p class="block confirm">
		La remise à zéro a été effectuée. Une sauvegarde a également été créée.</p>
	</p>
{else if $_GET.msg == 'REOPEN'}
	<p class="block confirm">
		L'exercice sélectionné a été réouvert.
	</p>
{/if}

<form method="post" action="{$self_url_no_qs}">
{if count($closed_years)}

<fieldset>
	<legend>Réouvrir un exercice clôturé</legend>
	<p class="help">
		À utiliser si vous avez clôturé un exercice par erreur. Attention, en comptabilité cette action est normalement exceptionnelle.
	</p>
	<p class="alert block">
		L'exercice sera réouvert, mais une écriture sera ajoutée au journal général indiquant que celui-ci a été réouvert après clôture. Cette écriture ne peut pas être supprimée.
	</p>
	<dl>
		{input type="select" options=$closed_years label="Exercicer à réouvrir" name="year"}
	</dl>
	<p>
		{csrf_field key="reopen_year"}
		{button type="submit" name="reopen_ok" label="Réouvrir l'exercice sélectionné" shape="reset"}
	</p>
</fieldset>
{/if}

{if ENABLE_TECH_DETAILS && $storage_backend != 'SQLite'}
	<h2 class="ruler">Stockage des fichiers</h2>
	{if !$quota_used}
	<fieldset>
		<legend>Migration de stockage de fichiers</legend>
		<p class="alert block">
			Les fichiers seront <strong>supprimés</strong> de la base de données après avoir été recopiés vers '{$storage_backend}'.
		</p>
		<p class="error block">
			Sauvegarde fortement recommandée avant de procéder à cette opération !
		</p>
		<p class="help">Cette opération peut prendre quelques minutes.</p>
		<p>
			{csrf_field key="migrate_backend"}
			{button type="submit" name="migrate_backend_ok" label="Copier tous les fichiers vers %s et les supprimer de la base de données"|args:$storage_backend shape="right"}
		</p>
	</fieldset>
	{else}
	<fieldset>
		<legend>Recopier les fichiers dans la base de données</legend>
		<p class="alert block">
			Les fichiers ne seront pas effacés de {$storage_backend} mais simplement recopiés dans la base de données.
		</p>
		<p class="help">Cette opération peut prendre quelques minutes. Elle est utile pour migrer entre deux systèmes de fichiers différents.</p>
		<p>
			{csrf_field key="migrate_back"}
			{button type="submit" name="migrate_back_ok" label="Copier tous les fichiers de %s vers la base de données"|args:$storage_backend shape="right"}
		</p>
	</fieldset>
	{/if}
{/if}
</form>

<h2 class="ruler">Actions destructrices</h2>

<form method="post" action="{$self_url_no_qs}">

<fieldset>
	<legend>Remise à zéro</legend>
	<p class="block error">
		Attention : toutes les données seront effacées&nbsp;! Ceci inclut les membres, les écritures comptables, les pages du wiki, etc.
		Seul votre compte membre sera re-créé avec le même email et mot de passe.
	</p>
	<p class="help">
		Une sauvegarde sera automatiquement créée avant de procéder à la remise à zéro.
	</p>
	<dl>
		<dt><label for="f_passe_verif">Votre mot de passe</label> (pour vérification)</dt>
		<dd><input type="password" name="passe_verif" id="f_passe_verif" /></dd>
	</dl>
	<p>
		{csrf_field key="reset"}
		{button type="submit" name="reset_ok" label="Oui, je veux remettre à zéro" shape="delete"}
	</p>
</fieldset>

</form>


{include file="admin/_foot.tpl"}