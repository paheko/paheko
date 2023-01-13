{include file="admin/_head.tpl" title="Inscrire à une activité" current="membres/services"}

{include file="services/_nav.tpl" current="save" fee=null service=null}

{form_errors}

<form method="post" action="subscribe.php" data-focus="button">

	<fieldset>
		<legend>Inscrire à une activité</legend>
		<dl>
			{input type="radio-btn" name="choice" value="1" label="Sélectionner des membres" default=1}
			{input type="radio-btn" name="choice" value="2" label="Recopier depuis une activité" help="Utile si vous avez une cotisation par année civile par exemple : copie les membres inscrits l'année précédente dans la nouvelle année."}
		</dl>
	</fieldset>

	<fieldset class="c1">
		<legend>Inscrire des membres</legend>
		<dl>
			{input type="list" name="users" required=true label="Membres à inscrire" target="!membres/selector.php" multiple=true}
		</dl>
	</fieldset>

	<fieldset class="c2">
		<legend>Recopier depuis une activité</legend>
		<dl>
			{input type="select_groups" name="copy" label="Activité à recopier" options=$services required=true default=0}
			{input type="checkbox" name="copy_only_paid" value="1" label="Ne recopier que les membres dont l'inscription est payée"}
		</dl>
	</fieldset>

	<p class="submit">
		<input type="hidden" name="paid" value="1" />
		{button type="submit" name="next" label="Continuer" shape="right" class="main"}
	</p>
</form>

<script type="text/javascript">
{literal}
function selectChoice() {
	let choice = $('#f_choice_1').form.choice.value;
	g.toggle('.c1', choice == 1);
	g.toggle('.c2', choice == 2);
}

selectChoice();
$('#f_choice_1').onchange = selectChoice;
$('#f_choice_2').onchange = selectChoice;
{/literal}
</script>

{include file="admin/_foot.tpl"}