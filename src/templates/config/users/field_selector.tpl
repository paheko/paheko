{include file="_head.tpl" title="Sélectionner un champ"}

<table class="list">
	<tbody>
	{foreach from=$list item="label" key="key"}
		<tr>
			<th>
				{$label}
			</th>
			<td class="actions">
				<button class="icn-btn" value="{$key}" data-label="{$label}" data-icon="&rarr;">Sélectionner</button>
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

{include file="_foot.tpl"}