{{if !$module.config}}
	{{* Valeurs par défaut *}}
	{{:assign var="module.config"
		objet_asso=""
		type_asso=""
		art200=false
		art238=false
		art978=false
	}}
	{{:assign var="module.config.comptes_don.754" value="754 — Ressources liées à la générosité du public"}}
	{{:assign var="module.config.comptes_don_nature.75412" value="75412 — Abandons de frais par les bénévoles"}}
	{{:assign var="module.config.comptes_especes.530" value="530 — Caisse"}}
	{{:assign var="module.config.comptes_cheques.5112" value="5112 — Chèques à encaisser"}}
	{{:assign var="module.config.champs_adresse"
		adresse="adresse"
		code_postal="code_postal"
		ville="ville"
	}}
{{/if}}