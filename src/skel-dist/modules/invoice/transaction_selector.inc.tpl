{{:admin_header title="Sélectionner un compte"}}

<form method="post" action="{{$request_url}}">
	<h2 class="ruler">
		<input type="text" placeholder="Recherche rapide d'écriture" value="{{$_POST.q}}" name="q" />
		<input type="submit" value="Chercher &rarr;" />
	</h2>
</form>

{{if $_POST.q}}
	{{if $_POST.q|parse_date}}
		{{:assign search_where="date = :searched_date" searched_date=$_POST.q|parse_date}}
	{{else}}
		{{:assign searched_text=$_POST.q|trim|regexp_replace:'/[!%_]/':'!$0'}}
		{{:assign search_where="((label LIKE :searched_text ESCAPE '!') OR (reference LIKE :searched_text ESCAPE '!'))" searched_text="%%%s%%"|args:$searched_text}}
	{{/if}}

	<table class="list">
		<tbody>
		{{#sql tables="acc_transactions" where=$search_where :searched_text=$searched_text :searched_date=$searched_date}}
			<tr>
				<th>
					{{$label}}{{if $reference}} - {{$reference}}{{/if}} - {{$date}}{{if $notes}} - {{$notes}}{{/if}}
				</th>
				<td class="actions">
					<button class="icn-btn" value="{{$id}}" data-label="{{$label}}" data-icon="&rarr;">Sélectionner</button>
				</td>
			</tr>
		{{/sql}}
		</tbody>
	</table>
{{/if}}

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

{{:admin_footer}}
