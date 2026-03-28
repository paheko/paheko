{include file="_head.tpl" title="Options développeur⋅euse" current="config"}

{include file="./_nav.tpl" current="options"}

<dl class="large">
	<dt>Vérifications</dt>
	<dd>
		{linkbutton shape="check" label="Vérifier l'intégrité (integrity_check)" href="query.php?pragma=integrity_check"}
	</dd>
	<dd>
		{linkbutton shape="check" label="Vérifier les clés étrangères (foreign_key_check)" href="query.php?pragma=foreign_key_check"}
	</dd>

	<dt>Profileur</dt>
	<dd class="help">
		Le profileur est une barre qui s'affiche en bas des pages, permettant de voir le nombre de requêtes exécutées dans une page, le temps  mis à l'exécution, ainsi que de voir la liste des requêtes exécutées.<br/>
		Il est principalement utile pour le développement de modules.<br />
		S'il est activé, il ne s'affichera que pour vous et votre adresse IP, et pas pour les autres membres connectés.
	</dd>
	<dd>
		{if $has_profiler}
			{linkbutton shape="uncheck" label="Désactiver le profileur" href="?profiler=0"}
		{else}
			{linkbutton shape="check" label="Activer le profileur" href="?profiler=1"}
		{/if}
	</dd>

	{if ENABLE_TECH_DETAILS}
		<dt>Reconstruire</dt>
		<dd class="help">Permet de reconstruire la base de données (VACUUM). Dans certains cas cela réduit l'espace disque utilisé.</dd>
		<dd>
			{linkbutton shape="reload" label="Reconstruire" href="query.php?pragma=vacuum"}
		</dd>
	{/if}

</dl>

{include file="_foot.tpl"}
