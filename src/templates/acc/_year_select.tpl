<nav class="acc-year">
	<header>
		<strong>Exercice sélectionné&nbsp;:</strong>
		<div>
			<h3>{$current_year.label} — {$current_year.start_date|date_short} au {$current_year.end_date|date_short}</h3>
			{tag preset=$current_year->getStatusTagPreset()}
		</div>
	</header>
	<footer>
		{linkbutton label="Changer d'exercice" href="!acc/years/select.php?from=%s"|args:rawurlencode($self_url) shape="settings" target="_dialog"}
	</footer>
</nav>
