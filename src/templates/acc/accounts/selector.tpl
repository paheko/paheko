{include file="admin/_head.tpl" title="Sélectionner un compte" body_id="popup" is_popup=true}

{if isset($grouped_accounts)}

	{foreach from=$grouped_accounts key="group_name" item="accounts"}
		<h2 class="ruler">{$group_name}</h2>

		<table class="list">
			<tbody>
			{foreach from=$accounts item="account"}
				<tr>
					<td>{$account.code}</td>
					<th>{$account.label}</th>
					<td class="desc">{$account.description}</td>
					<td class="actions">
						<button class="icn-btn" value="{$account.code}" data-label="&lt;b&gt;{$account.code}&lt;/b&gt; — {$account.label}" data-icon="&rarr;">Sélectionner</button>
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	{/foreach}

{/if}

{literal}
<script type="text/javascript">
var buttons = document.querySelectorAll('button');

buttons.forEach((e) => {
	e.onclick = () => {
		window.parent.inputListSelected(e.value, e.getAttribute('data-label'));
	};
});

buttons[0].focus();

var rows = document.querySelectorAll('table tr');

rows.forEach((e) => {
	e.className = 'clickable';
	e.onclick = (evt) => {
		if (evt.target.tagName && evt.target.tagName == 'BUTTON') {
			return;
		}

		e.querySelector('button').click();
	};
});
</script>
{/literal}

{include file="admin/_foot.tpl"}