<details>
	<summary class="help block">
		Attention&nbsp;: dans cette vue simplifiée,
		{if $type == Entities\Accounting\Account::TYPE_THIRD_PARTY}les dettes de ce tiers envers l'association apparaissent au crédit (positif) et les créances au débit (négatif)&nbsp;!
		{elseif $type}les écritures apparaissent tels que sur le relevé de banque ou le journal de caisse&nbsp;!
		{else}les comptes de banque, caisse, dépenses et tiers apparaissent de manière «&nbsp;simplifiée&nbsp;»&nbsp;!
		{/if}
		C'est l'inverse de la {if $link}<a href="{$link}">{/if}la vue comptable{if $link}</a>{/if} en comptabilité en partie double.</summary>
	<div class="help block">
		<p>L'extrait de compte fourni par la banque fonctionne « à l'envers », parce qu'il est établi du point de vue de la banque&nbsp;:</p>
		<ul>
			<li>les sommes versées sur votre compte (salaires etc.) constituent pour elle une ressource ( = crédit ; simultanément, cela augmente la dette de la banque à votre égard, ou réduit votre dette à son égard si vous êtes «&nbsp;débiteur&nbsp;»),</li>
			<li>les sommes retirées (paiement de chèques, carte bleue, etc.) constituent une utilisation (&nbsp;=&nbsp;débit).</li>
		</ul>
		<p>Du point de vue du client de la banque, dans une comptabilité en partie double, ce que la banque appelle crédit (une entrée d'argent) est un débit (c'est une utilisation de l'argent), et inversement (un débit pour la banque est une ressource de son client, donc, pour lui, un crédit). (<a href="https://fr.wikipedia.org/wiki/Comptabilit%C3%A9_en_partie_double">Source Wikipedia</a>)</p>
	</div>
</details>
