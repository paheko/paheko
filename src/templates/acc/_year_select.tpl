<nav class="acc-year">
	<h4>Exercice sélectionné&nbsp;:</h4>
	<h3>{$current_year.label} — {$current_year.start_date|date_fr:'d/m/Y'} au {$current_year.end_date|date_fr:'d/m/Y'}</h3>
	<footer>{linkbutton label="Changer d'exercice" href="!acc/years/select.php?from=%s"|args:$self_url shape="settings"}</footer>
</nav>
