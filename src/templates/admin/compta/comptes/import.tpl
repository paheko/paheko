{include file="admin/_head.tpl" title="Plan comptable" current="compta/categories"}

<ul class="actions">
	<li><a href="{$admin_url}compta/comptes/">Plan comptable</a></li>
	<li class="current"><a href="?import">Import / remise à zéro</a></li>
	<li><a href="?export=plan">Exporter le plan en format JSON</a></li>
</ul>

{form_errors}

{if $confirm}
<p class="confirm">
	{if $confirm == 'import'}L'import s'est correctement déroulé.
	{elseif $confirm == 'reset'}Le plan comptable a bien été remis à zéro.{/if}
</p>
{/if}

<form method="post" action="{$self_url}" enctype="multipart/form-data">

	<fieldset>
		<legend>Importer un plan comptable</legend>
		<p class="help">
			Toute modification actuelle du plan comptable sera perdue.<br />
			Les comptes associés à des écritures ou des comptes bancaires ne seront pas supprimés.
		</p>
		<dl>
			<dt><label for="f_file">Fichier à importer</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
			<dd><input type="file" name="upload" id="f_file" required="required" /></dd>
			<dt><label for="f_type">Format de fichier</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
			<dd>
				<input type="radio" name="format" id="f_format_json" value="json" {*form_field name="format" checked="json"*} checked="checked" />
				<label for="f_format_json">Plan comptable au format JSON de plan comptable Garradin</label>
			</dd>
		</dl>

		<p class="submit">
			{csrf_field key="plan_import"}
			<input type="submit" name="import" value="Importer &rarr;" />
		</p>
	</fieldset>

</form>

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Remise à zéro du plan comptable</legend>
		<p class="help">
			Permet de rétablir le plan comptable par défaut de Garradin.<br />
			Vos modifications personnelles seront perdues, assurez-vous d'en avoir une copie avant en cas de problèmes (bouton «&nbsp;Exporter le plan&nbsp;»).
		</p>

		<p class="submit">
			{csrf_field key="plan_reset"}
			<input type="submit" name="reset" value="Rétablir le plan comptable &rarr;" />
		</p>

	</fieldset>

</form>