<nav class="acc-year">
	<form method="get" action="{$self_url}">
		<fieldset>
			<legend>Exercice sélectionné</legend>
			<p>
				<?php $list = Accounting\Years::listOpen(); ?>
				{if count($list) == 1}
					<?php $year = current($list); ?>
					{$year.label} — {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}
				{else}
					<select name="change_year" onchange="this.form.submit();" title="Changer d'exercice">
					{foreach from=$list item="year"}
						<option value="{$year.id}"{if $year.id == $current_year_id} selected="selected"{/if}>{$year.label} — {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}</option>
					{/foreach}
					</select>
					<input type="submit" value="Changer &rarr;" />
				{/if}
			</p>
		</fieldset>
	</form>
</nav>
