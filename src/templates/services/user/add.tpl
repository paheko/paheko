{include file="admin/_head.tpl" title="Inscrire à une activité" current="membres/services"}

{include file="services/_nav.tpl" current="save" fee=null service=null}

{form_errors}

<form method="post" action="subscribe.php" data-focus="button">

	<fieldset>
		<legend>Inscrire à une activité</legend>
		<dl>
			{input type="radio-btn" name="choice" value="1" label="Sélectionner des membres" default=1}
			{input type="radio-btn" name="choice" value="2" label="Recopier depuis une activité" help="Utile si vous avez une cotisation par année civile par exemple : copie les membres inscrits l'année précédente dans la nouvelle année."}
			{input type="radio-btn" name="choice" value="3" label="Tous les membres d'un groupe" help="Vous pouvez utiliser le groupe 'Membres actifs par exemple ou tout autre groupe personnalisé"}
			{input type="radio-btn" name="choice" value="4" label="Résultats de recherche enregistrée" help="Utilisez les résultat de vos recherches enregistrées"}
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
	<fieldset class="c3">
        <legend>Tous les membres d'un groupe</legend>
		{input type="select" name="category" label="Groupe à inscrire" options=$categories  required=true  default=1}
	</fieldset>
	<fieldset class="c4">
		<legend>Résultats de recherche enregistrée</legend>
		{input type="select" name="recherche" label="Groupe à inscrire" options=$recherches  required=true}
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
	g.toggle('.c3', choice == 3);
	g.toggle('.c4', choice == 4);
}

$('#f_choice_1').onchange = selectChoice;
$('#f_choice_2').onchange = selectChoice;
$('#f_choice_3').onchange = selectChoice;
$('#f_choice_4').onchange = selectChoice;

setTimeout(selectChoice, 500);

{/literal}
</script>

{include file="admin/_foot.tpl"}