{{if !$module.config}}
	{{* Valeurs par défaut *}}
	{{:assign var="module.config"
		objet_asso=""
		type_asso=""
		art200=false
		art238=false
		art978=false
	}}
	{{:assign var="module.config.comptes_don." value="754"}}
	{{:assign var="module.config.comptes_don_nature." value="75412"}}
	{{:assign var="module.config.comptes_especes." value="530"}}
	{{:assign var="module.config.comptes_cheques." value="5112"}}
	{{:assign var="module.config.champs_adresse"
		0="adresse"
		1="code_postal"
		2="ville"
	}}
{{/if}}