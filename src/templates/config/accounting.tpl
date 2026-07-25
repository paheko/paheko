{include file="_head.tpl" title="Comptabilité — configuration" current="config"}

{include file="./_menu.tpl" current="index" sub_current="acc"}

{if $_GET.msg == 'SAVED'}
	<p class="block confirm">
		La configuration a été enregistrée.
	</p>
{/if}

{form_errors}

<form method="post" action="{$self_url_no_qs}">
<fieldset>
	<legend>Configuration des projets</legend>
	<dl>
		{input type="checkbox" name="analytical_mandatory" source=$config label="Obliger à préciser un projet pour toutes les écritures" value=1}
		<dd class="help">
			Si cette case est cochée, il sera obligatoire d'indiquer un projet lors de la saisie ou la modification d'une écriture.<br />
			Si l'affectation ne se fait qu'aux comptes de charge et de produit (ci-dessous), alors les autres types de compte ne comporteront jamais de projet.
		</dd>
	</dl>
</fieldset>
<fieldset>
	<legend>Affectation des projets</legend>
	<dl>
		{input type="radio-btn" prefix_required=true prefix_title="Affecter les projets analytiques…" name="analytical_set_all" value="0" label="Seulement aux comptes de charge et de produit" source=$config help="Fonctionnement habituel en comptabilité, recommandé."}
		{input type="radio-btn" name="analytical_set_all" value="1" label="À tous les comptes" source=$config help="Permet de suivre la caisse, banque, comptes de tiers, etc. dans un projet."}
	</dl>
</fieldset>
<p>
	{csrf_field key=$csrf_key}
	{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
</p>
</form>

{include file="_foot.tpl"}