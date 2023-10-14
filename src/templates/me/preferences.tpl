{include file="_head.tpl" title="Mes préférences" current="me"}

{include file="./_nav.tpl" current="preferences"}

{if $ok !== null}
<p class="confirm block">
	Les modifications ont bien été enregistrées.
</p>
{/if}


<form method="post" action="{$self_url_no_qs}">
	<fieldset>
		<legend>Mes préférences</legend>
		<dl>
			{input type="select" name="dark_theme" label="Thème" required=true source=$preferences options=$themes_options default=false}
			{input type="select" name="force_handheld" label="Taille d'écran" required=true source=$preferences options=$handheld_options default=false}
			{input type="select" name="page_size" label="Nombre d'éléments par page dans les listes" required=true source=$preferences options=$page_size_options default=100 help="Par exemple dans la liste des membres."}
			{if $session->canAccess($session::SECTION_DOCUMENTS, $session::ACCESS_READ)}
			{input type="select" name="folders_gallery" label="Affichage des listes de documents" required=true source=$preferences options=$folders_options default=true}
			{/if}
			{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ)}
				<dt><label for="f_accounting_expert_0">Affichage de la comptabilité</label></dt>
				{input type="radio-btn" name="accounting_expert" value=0 label="Simplifié" default=0 source=$preferences help="Conseillé. Pour les novices en comptabilité, affiche notamment les comptes de banque tels qu'ils apparaissent sur les relevés bancaires."}
				{input type="radio-btn" name="accounting_expert" value=1 label="Expert" source=$preferences help="Si vous avez une bonne expérience de la comptabilité en partie double. Affiche les journaux de compte au sens de la comptabilité en partie double."}
			{/if}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}