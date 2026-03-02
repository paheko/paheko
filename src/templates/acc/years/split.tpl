{include file="_head.tpl" title="Déplacer des écritures" current="acc/years"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<p class="help">
		Cette action permet de déplacer toutes les écritures situées entre deux dates vers un autre exercice.<br />
		Utile si on souhaite "séparer" un exercice en deux par exemple.
	</p>

	<fieldset>
		<legend>Dates</legend>
		<dl>
			{input type="date" label="Date de début" name="start" default=$year.start_date required=true}
			{input type="date" label="Date de fin" name="end" default=$year.end_date required=true}
		</dl>
		<p class="alert block">
			Toutes les écritures situées entre ces deux dates (y compris à ces dates) seront déplacées dans l'exercice sélectionné.
		</p>
	</fieldset>

	<fieldset>
		<legend>Exercice cible</legend>
		<dl>
			{input type="select" name="target" options=$years label="Déplacer les écritures vers cet exercice" help="Toutes les écritures situées entre les dates sélectionnées (y compris à ces dates) seront déplacées dans l'exercice sélectionné." required=true}
		</dl>
	</fieldset>

	<p class="alert block">
		Attention&nbsp;: cette action ne modifie pas les dates des écritures.
		L'exercice cible doit donc avoir des dates qui englobent les écritures déplacées.
	</p>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="split" label="Déplacer" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}