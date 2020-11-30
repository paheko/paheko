{include file="admin/_head.tpl" title="Sélectionner un compte" body_id="popup" is_popup=true}

<form method="get" action="{$self_url_no_qs}">
	<h2 class="ruler">
		<input type="text" placeholder="Recherche rapide de membre" value="{$query}" name="q" />
		<input type="submit" value="Chercher &rarr;" />
	</h2>
</form>

<table class="list">
	<tbody>
    {foreach from=$list item="row"}
        <tr>
        	<td class="num">
        		{$row.numero}
        	</td>
            <th>
                {$row.identite}
            </th>
            <td class="actions">
				<button class="icn-btn" value="{$row.id}" data-label="{$row.numero} — {$row.identite}" data-icon="&rarr;">Sélectionner</button>
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{literal}
<script type="text/javascript">
var buttons = document.querySelectorAll('button');

buttons.forEach((e) => {
	e.onclick = () => {
		window.parent.g.inputListSelected(e.value, e.getAttribute('data-label'));
	};
});

if (buttons.length) {
	buttons[0].focus();
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

document.querySelector('input').focus();
</script>
{/literal}

{include file="admin/_foot.tpl"}