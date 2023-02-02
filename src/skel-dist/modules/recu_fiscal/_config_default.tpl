{{if !$module.config}}
	{{* Valeurs par défaut *}}
	{{:assign var="module[config]"
		objet_asso=""
		type_asso=""
		comptes_don="754"
		comptes_don_nature="75412"
		comptes_especes="530"
		comptes_cheques="5112"
		art200=false
		art238=false
		art978=false
	}}
	{{:assign var="module[config][champs_adresse]"
		0="adresse"
		1="code_postal"
		2="ville"
	}}
{{/if}}