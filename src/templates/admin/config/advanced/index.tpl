{include file="admin/_head.tpl" title="Fonctions avancées" current="config" custom_css=["styles/config.css"]}

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

{if count($closed_years)}
<form method="post" action="{$self_url_no_qs}">

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
</form>
{/if}

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