<dd class="help">
	Règles à suivre pour créer le fichier CSV&nbsp;:
	<ul>
		<li>Il est recommandé d'utiliser LibreOffice pour créer le fichier CSV</li>
		<li>Le fichier doit être en UTF-8</li>
		<li>Le séparateur doit être le point-virgule ou la virgule</li>
		<li>Cocher l'option <em>"Mettre en guillemets toutes les cellules du texte"</em></li>
		<li>Le fichier doit comporter les colonnes suivantes : <em>{$csv->getColumnsString()}</em></li>
		{if $columns = $csv->getMandatoryColumnsString()}<li>Le fichier peut également comporter les colonnes suivantes : <em>{$columns}</em></li>{/if}
	</ul>
</dd>