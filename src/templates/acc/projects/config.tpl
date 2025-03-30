{include file="_head.tpl" title="Projets - configuration" current="acc/years"}

{include file="./_nav.tpl" current="config" order_code=null}

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
			Si cette case est cochée, il sera obligatoire d'indiquer un projet lors de la saisie ou la modification d'une écriture.
		</dd>
		<dt><label for="f_analytical_set_all_0">Lors de la saisie d'une écriture simplifiée (recette ou dépense), affecter le projet analytique…</label></dt>
		{input type="radio" name="analytical_set_all" value="1" label="à tous les comptes" source=$config help="permet de suivre la caisse, banque, comptes de tiers, etc. dans un projet"}
		{input type="radio" name="analytical_set_all" value="0" label="seulement aux comptes de charge et de produit" source=$config}
	</dl>
</fieldset>
<p>
	{csrf_field key="save_config"}
	{button type="submit" name="save_config" label="Enregistrer" shape="right" class="main"}
</p>
</form>

{include file="_foot.tpl"}