<fieldset>
	<legend>Sélectionner une feuille</legend>
	<p class="help">Ce fichier comporte plusieurs feuilles. Merci de sélectionner la feuille à importer.</p>
	<dl>
		{input type="select" required=true name="sheet" label="Feuille à importer" options=$csv->listSheets()}
	</dl>
</fieldset>