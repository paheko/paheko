{include file="_head.tpl" title="Mentions légales" layout="public" custom_css="/content.css"}

<div class="web-content">
	<p>
		{linkbutton shape="left" href=$www_url label="Retour au site"}
	</p>
	<h1>Mentions légales</h1>
	<p>
		Nom&nbsp;:<br />
		<strong>{$config.org_name}</strong>
	</p>
	<p>
		Adresse&nbsp;:<br />
		{if !$config.org_address}
			Non renseignée
		{else}
			{$config.org_address|escape|nl2br}
		{/if}
	</p>
	<p>
		Téléphone&nbsp;:<br />
		{if $config.org_phone}
			{$config.org_phone|protect_contact:'tel'|raw}
		{else}
			Non renseigné
		{/if}
	</p>
	<p>
		Adresse e-mail&nbsp;:<br />
		{if $config.org_email}
			{$config.org_email|protect_contact|raw}
		{else}
			Non renseigné
		{/if}
	</p>
	<p>
		Hébergeur&nbsp;:<br />
		{if LEGAL_HOSTING_DETAILS}
			<?=LEGAL_HOSTING_DETAILS?>
		{else}
			<strong>{$config.org_name}</strong><br />
			{$config.org_address|escape|nl2br}
		{/if}
	</p>
</div>

{include file="_foot.tpl"}