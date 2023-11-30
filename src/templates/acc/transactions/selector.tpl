{include file="_head.tpl" title="Sélectionner une ou des écritures"}

<form method="post" action="{$self_url}">
	<h2 class="ruler">
		{input type="text" placeholder="Numéro ou libellé d'écriture" value="{$query}" name="q"}
		{input type="select" name="id_year" default_empty="— Tous les exercices —" options=$years}
		{button shape="search" type="submit" label="Chercher"}
	</h2>
</form>

{if $list}
	<table class="list">
		<tbody>
		{foreach from=$list item="row"}
			<tr>
				<td class="num"><a>#{$row.id}</a></td>
				<th>
					{$row.label}
				</th>
				<td>
					{$row.date|date_short}
				</td>
				<td>
					<?php $year = $years[$row->id_year];?>
					{$year}
				</td>
				<td class="actions">
					<button class="icn-btn" value="{$row.id}" data-label="{$row.id}" data-icon="&rarr;">Sélectionner</button>
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>


	{if empty($row)}
		<p class="alert block">
			Aucun résultat.
		</p>
	{/if}
{/if}


{literal}
<script type="text/javascript">
var buttons = document.querySelectorAll('button[data-label]');

buttons.forEach((e) => {
	e.onclick = () => {
		window.parent.g.inputListSelected(e.value, e.getAttribute('data-label'));
	};
});

if (buttons.length) {
	buttons[0].focus();
}
else {
	document.querySelector('input').focus();
}

var rows = document.querySelectorAll('table tbody tr');

if (rows.length == 1) {
	rows[0].querySelector('button').click();
}

rows.forEach((e) => {
	e.classList.add('clickable');

	e.onclick = (evt) => {
		if (evt.target.tagName && evt.target.tagName == 'BUTTON') {
			return;
		}

		e.querySelector('button').click();
	};
});
</script>
{/literal}

{include file="_foot.tpl"}