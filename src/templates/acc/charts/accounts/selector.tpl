{include file="admin/_head.tpl" title="Sélectionner un compte" body_id="popup" is_popup=true}

{if empty($grouped_accounts) && empty($accounts)}
	<p class="block alert">Le plan comptable ne comporte aucun compte de ce type. Pour afficher des comptes ici, les <a href="{$www_url}admin/acc/charts/accounts/all.php?id={$chart.id}" target="_blank">modifier dans le plan comptable</a> en sélectionnant le type de compte favori voulu.</td>

{elseif isset($grouped_accounts)}

	{foreach from=$grouped_accounts item="group"}
		<h2 class="ruler">{$group.label}</h2>

		<table class="list">
			<tbody>
			{foreach from=$group.accounts item="account"}
				<tr>
					<td>{$account.code}</td>
					<th>{$account.label}</th>
					<td class="desc">{$account.description}</td>
					<td class="actions">
						<button class="icn-btn" value="{$account.id}" data-label="{$account.code} — {$account.label}" data-icon="&rarr;">Sélectionner</button>
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	{/foreach}

{else}

	<h2 class="ruler">
		<input type="text" placeholder="Recherche rapide" id="lookup" />
		<label>{input type="checkbox" name="typed_only" value=1 default=1} N'afficher que les comptes favoris</label>
	</h2>

	<table class="accounts">
		<tbody>
		{foreach from=$accounts item="account"}
			<tr class="account-level-{$account.code|strlen} t{$account.type}">
				<td>{$account.code}</td>
				<th>{$account.label}</th>
				<td>
				{if $account.type}
					{icon shape="star"} <?=Entities\Accounting\Account::TYPES_NAMES[$account->type]?>
				{/if}
				</td>
				<td class="actions">
					<button class="icn-btn" value="{$account.id}" data-label="{$account.code} — {$account.label}" data-icon="&rarr;">Sélectionner</button>
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

{/if}

{literal}
<script type="text/javascript">

RegExp.escape = function(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
};

function normalizeString(str) {
	return str.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
}

var buttons = document.querySelectorAll('button');

buttons.forEach((e) => {
	e.onclick = () => {
		window.parent.g.inputListSelected(e.value, e.getAttribute('data-label'));
	};
});

buttons[0].focus();

var rows = document.querySelectorAll('table tr');

rows.forEach((e) => {
	e.classList.add('clickable');
	var l = e.querySelector('td').innerText + ' ' + e.querySelector('th').innerText;
	e.setAttribute('data-search-label', normalizeString(l));

	e.onclick = (evt) => {
		if (evt.target.tagName && evt.target.tagName == 'BUTTON') {
			return;
		}

		e.querySelector('button').click();
	};
});

var q = document.getElementById('lookup');

if (q) {
	q.onkeyup = (e) => {
		var query = new RegExp(RegExp.escape(normalizeString(q.value)), 'i');

		rows.forEach((elm) => {
			if (elm.getAttribute('data-search-label').match(query)) {
				elm.style.display = null;
			}
			else {
				elm.style.display = 'none';
			}
		});

	};

	q.focus();
}

var o = document.getElementById('f_typed_only_1');

if (o) {
	o.onchange = () => {
		g.toggle('.t0', !o.checked);
	};
	g.toggle('.t0', !o.checked);
}
</script>
{/literal}

{include file="admin/_foot.tpl"}