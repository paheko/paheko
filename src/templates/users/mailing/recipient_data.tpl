{include file="_head.tpl" title="Données du destinataire" current="users/mailing"}

<p class="help">Vous pouvez copier la variable (colonne de gauche) dans le corps du message&nbsp;:
	elle sera remplacée dans le message par le contenu (colonne à droite) spécifique à chaque destinataire.</p>

<table class="list auto center">
	<thead>
		<tr>
			<td>Variable</td>
			<td>Contenu</td>
		</tr>
	</thead>
	<tbody>
		{foreach from=$data key="name" item="value"}
		<tr>
			<td><code>{ldelim}{ldelim}${$name}{rdelim}{rdelim}</code></td>
			<td>{$value|escape|nl2br}</td>
		</tr>
		{/foreach}
	</tbody>
</table>

{include file="_foot.tpl"}