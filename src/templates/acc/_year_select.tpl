<nav class="acc-year">
	<header>
		<strong>Exercice sélectionné&nbsp;:</strong>
		<div>
			<h3>{$current_year.label} — {$current_year.start_date|date_short} au {$current_year.end_date|date_short}</h3>
			{if $current_year.closed}{tag label="Clôturé"}{else}{tag label="En cours" color="darkgreen"}{/if}
		</div>
	</header>
	<footer>
		{linkbutton label="Changer d'exercice" href="!acc/years/select.php?from=%s"|args:rawurlencode($self_url) shape="settings" target="_dialog"}
	</footer>
</nav>
