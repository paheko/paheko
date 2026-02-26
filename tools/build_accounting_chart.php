<?php

$plan = <<<EOF_PLAN
Classe 1 — Comptes de capitaux (Fonds propres, emprunts et dettes assimilés)
10 Capital et réserves
101 Capital
1011 Capital souscrit - non appelé
1012 Capital souscrit - appelé, non versé
1013 Capital souscrit - appelé, versé
10131 Capital non amorti
10132 Capital amorti
1018 Capital souscrit soumis à des réglementations particulières
102 Fonds fiduciaires
104 Primes liées au capital
1041 Primes d'émission
1042 Primes de fusion
1043 Primes d'apport
1044 Primes de conversion d'obligations en actions
1045 Bons de souscription d'actions
105 Écarts de réévaluation
106 Réserves
1061 Réserve légale
1062 Réserves indisponibles
1063 Réserves statutaires ou contractuelles
1064 Réserves réglementées
1068 Autres réserves
107 Écart d'équivalence
108 Compte de l'exploitant
109 Actionnaires : capital souscrit - non appelé
11 Report à nouveau
110 Report à nouveau - solde créditeur
119 Report à nouveau - solde débiteur
12 Résultat de l'exercice
120 Résultat de l'exercice - bénéfice
1209 Acomptes sur dividendes
129 Résultat de l'exercice – perte
13 Subventions d'investissement
131 Subventions d'investissement octroyées
139 Subventions d'investissement inscrites au compte de résultat
14 Provisions réglementées
143 Provisions réglementées pour hausse de prix
145 Amortissements dérogatoires
148 Autres provisions réglementées
15 Provisions
151 Provisions pour risques
1511 Provisions pour litiges
1512 Provisions pour garanties données aux clients
1514 Provisions pour amendes et pénalités
1515 Provisions pour pertes de change
1516 Provisions pour pertes sur contrats
1518 Autres provisions pour risques
152 Provisions pour charges
1521 Provisions pour pensions et obligations similaires
1522 Provisions pour restructurations
1523 Provisions pour impôts
1524 Provisions pour renouvellement des immobilisations - entreprises concessionnaires
1525 Provisions pour gros entretien ou grandes révisions
1526 Provisions pour remise en état
1527 Autres provisions pour charges
16 Emprunts et dettes assimilées
161 Emprunts obligataires convertibles
1618 Intérêts courus sur emprunts obligataires convertibles
162 Obligations représentatives de passifs nets remis en fiducie
163 Autres emprunts obligataires
1638 Intérêts courus sur autres emprunts obligataires
164 Emprunts auprès des établissements de crédit
1648 Intérêts courus sur emprunts auprès des établissements de crédit
165 Dépôts et cautionnements reçus
1651 Dépôts
1655 Cautionnements
1658 Intérêts courus sur dépôts et cautionnements reçus
166 Participation des salariés aux résultats
1661 Comptes bloqués
1662 Fonds de participation
1668 Intérêts courus sur participation des salariés aux résultats
167 Emprunts et dettes assortis de conditions particulières
1671 Émissions de titres participatifs
16718 Intérêts courus sur titres participatifs
1674 Avances conditionnées de l'État
16748 Intérêts courus sur avances conditionnées
1675 Emprunts participatifs
16758 Intérêts courus sur emprunts participatifs
168 Autres emprunts et dettes assimilées
1681 Autres emprunts
1685 Rentes viagères capitalisées
1687 Autres dettes
1688 Intérêts courus sur autres emprunts et dettes assimilées
169 Primes de remboursement des emprunts
17 Dettes rattachées à des participations
171 Dettes rattachées à des participations - groupe
174 Dettes rattachées à des participations - hors groupe
178 Dettes rattachées à des sociétés en participation
18 Comptes de liaison des établissements et sociétés en participation
181 Comptes de liaison des établissements
186 Biens et prestations de services échangés entre établissements - charges
187 Biens et prestations de services échangés entre établissements - produits
188 Comptes de liaison des sociétés en participation

Classe 2 — Comptes d'immobilisations
20 Immobilisations incorporelles et frais d’établissement
201 Frais d'établissement
2011 Frais de constitution
2012 Frais de premier établissement
20121 Frais de prospection
20122 Frais de publicité
2013 Frais d'augmentation de capital et d'opérations diverses - fusions, scissions, transformations
203 Frais de recherche et développement
205 Concessions et droits similaires, brevets, licences, marques, procédés, solutions informatiques, droits et valeurs similaires
206 Droit au bail
207 Fonds commercial
208 Autres immobilisations incorporelles
2081 Mali de fusion sur actifs incorporels
21 Immobilisations corporelles
211 Terrains
2111 Terrains nus
2112 Terrains aménagés
2113 Sous-sols et sur-sols
2114 Terrains de carrières (Tréfonds)
2115 Terrains bâtis
212 Agencements et aménagements de terrains
2121 Terrains nus
2122 Terrains aménagés
2123 Sous-sols et sur-sols
2124 Terrains de carrières (Tréfonds)
2125 Terrains bâtis
213 Constructions
2131 Bâtiments
2135 Installations générales - agencements - aménagements des constructions
2138 Ouvrages d'infrastructure
214 Constructions sur sol d'autrui
2141 Bâtiments
2145 Installations générales - agencements - aménagements des constructions
2148 Ouvrages d'infrastructure
215 Installations techniques, matériels et outillages industriels
2151 Installations complexes spécialisées
21511 Installations complexes spécialisées sur sol propre
21514 Installations complexes spécialisées sur sol d'autrui
2153 Installations à caractère spécifique
21531 Installations à caractère spécifique sur sol propre
21534 Installations à caractère spécifique sur sol d'autrui
2154 Matériels industriels
2155 Outillages industriels
2157 Agencements et aménagements des matériels et outillages industriels
218 Autres immobilisations corporelles
2181 Installations générales, agencements, aménagements divers
2182 Matériel de transport
2183 Matériel de bureau et matériel informatique
2184 Mobilier
2185 Cheptel
2186 Emballages récupérables
2187 Mali de fusion sur actifs corporels
22 Immobilisations mises en concession
229 Droits du concédant
23 Immobilisations en cours, avances et acomptes
231 Immobilisations corporelles en cours
232 Immobilisations incorporelles en cours
237 Avances et acomptes versés sur immobilisations incorporelles
238 Avances et acomptes versés sur immobilisations corporelles
26 Participations et créances rattachées à des participations
261 Titres de participation
2611 Actions
2618 Autres titres
262 Titres évalués par équivalence
266 Autres formes de participation
2661 Droits représentatifs d'actifs nets remis en fiducie
267 Créances rattachées à des participations
2671 Créances rattachées à des participations - groupe
2674 Créances rattachées à des participations - hors groupe
2675 Versements représentatifs d'apports non capitalisés - appel de fonds
2676 Avances consolidables
2677 Autres créances rattachées à des participations
2678 Intérêts courus
268 Créances rattachées à des sociétés en participation
2681 Principal
2688 Intérêts courus
269 Versements restant à effectuer sur titres de participation non libérés
27 Autres immobilisations financières
271 Titres immobilisés autres que les titres immobilisés de l'activité de portefeuille (droit de propriété)
2711 Actions
2718 Autres titres
272 Titres immobilisés (droit de créance)
2721 Obligations
2722 Bons
273 Titres immobilisés de l'activité de portefeuille
274 Prêts
2741 Prêts participatifs
2742 Prêts aux associés
2743 Prêts au personnel
2748 Autres prêts
275 Dépôts et cautionnements versés
2751 Dépôts
2755 Cautionnements
276 Autres créances immobilisées
2761 Créances diverses
2768 Intérêts courus
27682 Intérêts courus sur titres immobilisés (droit de créance)
27684 Intérêts courus sur prêts
27685 Intérêts courus sur dépôts et cautionnements
27688 Intérêts courus sur créances diverses
277 Actions propres ou parts propres
2771 Actions propres ou parts propres
2772 Actions propres ou parts propres en voie d’annulation
278 Mali de fusion sur actifs financiers
279 Versements restant à effectuer sur titres immobilisés non libérés
28 Amortissements des immobilisations
280 Amortissements des immobilisations incorporelles et des frais d’établissement
2801 Frais d'établissement
28011 Frais de constitution
28012 Frais de premier établissement
280121 Frais de prospection
280122 Frais de publicité
2803 Frais de développement
2805 Concessions et droits similaires, brevets, licences, solutions informatiques, droits et valeurs similaires
2806 Droit au bail
2807 Fonds commercial
2808 Autres immobilisations incorporelles
281 Amortissements des immobilisations corporelles
2812 Agencements, aménagements de terrains
2813 Constructions
2814 Constructions sur sol d'autrui
2815 Installations, matériel et outillage industriels
2818 Autres immobilisations corporelles
28187 Amortissement du mali de fusion sur actifs corporels
282 Amortissements des immobilisations mises en concession
29 Dépréciations des immobilisations
290 Dépréciations des immobilisations incorporelles
2901 Frais d’établissement
2903 Frais de développement
2905 Marques, procédés, droits et valeurs similaires
2906 Droit au bail
2907 Fonds commercial
2908 Autres immobilisations incorporelles
29081 Dépréciation du mali de fusion sur actifs incorporels
291 Dépréciations des immobilisations corporelles
2911 Terrains
2912 Agencements et aménagements de terrains
2913 Constructions
2914 Constructions sur sol d'autrui
2915 Installations techniques, matériels et outillages industriels
2918 Autres immobilisations corporelles
29187 Dépréciation du mali de fusion sur actifs corporels
292 Dépréciations des immobilisations mises en concession
293 Dépréciations des immobilisations en cours
2931 Immobilisations corporelles en cours
2932 Immobilisations incorporelles en cours
296 Dépréciations des participations et créances rattachées à des participations
2961 Titres de participation
2962 Titres évalués par équivalence
2966 Autres formes de participation
2967 Créances rattachées à des participations
2968 Créances rattachées à des sociétés en participation
297 Dépréciations des autres immobilisations financières
2971 Titres immobilisés autres que les titres immobilisés de l'activité de portefeuille (droit de propriété)
2972 Titres immobilisés (droit de créance)
2973 Titres immobilisés de l'activité de portefeuille
2974 Prêts
2975 Dépôts et cautionnements versés
2976 Autres créances immobilisées

Classe 3 — Comptes de stocks et en-cours
31 Matières premières et fournitures
32 Autres approvisionnements
321 Matières consommables
322 Fournitures consommables
3221 Combustibles
3222 Produits d'entretien
3223 Fournitures d'atelier et d'usine
3224 Fournitures de magasin
3225 Fournitures de bureau
326 Emballages
3261 Emballages perdus
3265 Emballages récupérables non identifiables
3267 Emballages à usage mixte
33 En-cours de production de biens
331 Produits en cours
335 Travaux en cours
34 En-cours de production de services
341 Études en cours
345 Prestations de services en cours
35 Stocks de produits
351 Produits intermédiaires
355 Produits finis
358 Produits résiduels ou matières de récupération
3581 Déchets
3585 Rebuts
3586 Matières de récupération
36 Stocks provenant d'immobilisations
37 Stocks de marchandises
38 Stocks en voie d'acheminement, mis en dépôt ou donnés en consignation
39 Dépréciations des stocks et en-cours
391 Dépréciations des matières premières et fournitures
392 Dépréciations des autres approvisionnements
393 Dépréciations des en-cours de production de biens
394 Dépréciations des en-cours de production de services
395 Dépréciations des stocks de produits
397 Dépréciations des stocks de marchandises

Classe 4 — Comptes de tiers
40 Fournisseurs et comptes rattachés
401 Fournisseurs
4011 Fournisseurs - Achats de biens et prestations de services
4017 Fournisseurs - Retenues de garantie
403 Fournisseurs - Effets à payer [Passif]
404 Fournisseurs d'immobilisations
4041 Fournisseurs - Achats d'immobilisations
4047 Fournisseurs d'immobilisations - Retenues de garantie
405 Fournisseurs d'immobilisations - Effets à payer [Passif]
408 Fournisseurs - Factures non parvenues [Passif]
4081 Fournisseurs
4084 Fournisseurs d'immobilisations
4088 Fournisseurs - Intérêts courus
409 Fournisseurs débiteurs [Actif]
4091 Fournisseurs - Avances et acomptes versés sur commandes
4096 Fournisseurs - Créances pour emballages et matériel à rendre
4097 Fournisseurs - Autres avoirs
40971 Fournisseurs d'exploitation
40974 Fournisseurs d'immobilisations
4098 RRR à obtenir et autres avoirs non encore reçus
41 Clients et comptes rattachés
411 Clients
4111 Clients - Ventes de biens ou de prestations de services
4117 Clients - Retenues de garantie
413 Clients - Effets à recevoir [Actif]
416 Clients douteux ou litigieux [Actif]
418 Clients - Produits non encore facturés [Actif]
4181 Clients - Factures à établir
4188 Clients - Intérêts courus
419 Clients créditeurs [Passif]
4191 Clients - Avances et acomptes reçus sur commandes
4196 Clients - Dettes sur emballages et matériels consignés
4197 Clients - Autres avoirs
4198 RRR à accorder et autres avoirs à établir
42 Personnel et comptes rattachés
421 Personnel - Rémunérations dues [Passif]
422 Comité social et économique
424 Participation des salariés aux résultats [Actif]
4246 Réserve spéciale
4248 Comptes courants
425 Personnel - Avances et acomptes et autres comptes débiteurs [Actif]
426 Personnel - Dépôts [Passif]
427 Personnel - Oppositions [Passif]
428 Personnel - Charges à payer [Passif]
4282 Dettes provisionnées pour congés à payer
4284 Dettes provisionnées pour participation des salariés aux résultats
4286 Autres charges à payer
43 Sécurité sociale et autres organismes sociaux [Passif]
431 Sécurité sociale [Passif]
437 Autres organismes sociaux [Passif]
438 Organismes sociaux - Charges à payer [Passif]
4382 Charges sociales sur congés à payer
4386 Autres charges à payer
439 Organismes sociaux - Produits à recevoir
44 État et autres collectivités publiques [Actif]
441 État - Subventions et aides à recevoir [Actif]
442 Contributions, impôts et taxes recouvrés pour le compte de l'État [Passif]
4421 Prélèvements à la source (Impôt sur le revenu)
4422 Prélèvements forfaitaires non libératoires
4423 Retenues et prélèvements sur les distributions
444 État - Impôts sur les bénéfices
445 État - Taxes sur le chiffre d'affaires [Actif]
4452 TVA due intracommunautaire
4455 Taxes sur le chiffre d'affaires à décaisser
44551 TVA à décaisser
44558 Taxes assimilées à la TVA
4456 Taxes sur le chiffre d'affaires déductibles
44562 TVA sur immobilisations
44563 TVA transférée par d'autres entités
44566 TVA sur autres biens et services
44567 Crédit de TVA à reporter
44568 Taxes assimilées à la TVA
4457 Taxes sur le chiffre d'affaires collectées
44571 TVA collectée
44578 Taxes assimilées à la TVA
4458 Taxes sur le chiffre d'affaires à régulariser ou en attente
44581 Acomptes - Régime simplifié d'imposition
44583 Remboursement de taxes sur le chiffre d'affaires demandé
44584 TVA récupérée d’avance
44586 Taxes sur le chiffre d’affaires sur factures non parvenues
44587 Taxes sur le chiffre d’affaires sur factures à établir
446 Obligations cautionnées [Actif]
447 Autres impôts, taxes et versements assimilés [Actif]
448 État - Charges à payer et produits à recevoir
4481 État - Charges à Payer
44811 Charges fiscales sur congés à payer
44812 Charges à payer
4482 État - Produits à recevoir
449 Quotas d’émission à acquérir [Passif]
45 Groupe et associés
451 Groupe
455 Associés - Comptes courants
4551 Principal
4558 Intérêts courus
456 Associés - Opérations sur le capital [Actif]
4561 Associés - Comptes d'apport en société
45611 Apports en nature
45615 Apports en numéraire
4562 Apporteurs - Capital appelé, non versé
45621 Actionnaires - Capital souscrit et appelé, non versé
45625 Associés - Capital appelé, non versé
4563 Associés - Versements reçus sur augmentation de capital
4564 Associés - Versements anticipés
4566 Actionnaires défaillants
4567 Associés - Capital à rembourser
457 Associés - Dividendes à payer [Passif]
458 Associés - Opérations faites en commun et en GIE
4581 Opérations courantes
4588 Intérêts courus
46 Débiteurs et créditeurs divers
462 Créances sur cessions d'immobilisations [Actif]
464 Dettes sur acquisitions de valeurs mobilières de placement [Passif]
465 Créances sur cessions de valeurs mobilières de placement [Actif]
467 Divers comptes débiteurs et produits à recevoir
468 Divers comptes créditeurs et charges à payer
47 Comptes transitoires ou d'attente
471 à 473 Comptes d'attente
474 Différences d’évaluation – Actif [Actif]
4741 Différences d'évaluation sur instruments financiers à terme - Actif [Actif]
4742 Différences d'évaluation sur jetons détenus - Actif [Actif]
4746 Différences d’évaluation de jetons sur des passifs - Actif [Actif]
475 Différences d’évaluation – Passif [Passif]
4751 Différences d'évaluation sur instruments financiers à terme - Passif [Passif]
4752 Différences d'évaluation sur jetons détenus - Passif [Passif]
4756 Différences d’évaluation de jetons sur des passifs - Passif [Passif]
476 Différence de conversion - Actif [Actif]
4761 Diminution des créances
4762 Augmentation des dettes
4768 Différences compensées par couverture de change
477 Différences de conversion - Passif [Passif]
4771 Augmentation des créances
4772 Diminution des dettes
4778 Différences compensées par couverture de change
478 Autres comptes transitoires
4781 Mali de fusion sur actif circulant
48 Comptes de régularisation
481 Frais d’émission des emprunts [Passif]
486 Charges constatées d'avance [Actif]
487 Produits constatés d'avance [Passif]
4871 Produits constatés d’avance sur jetons émis
488 Comptes de répartition périodique des charges et des produits
4886 Charges
4887 Produits
49 Dépréciations des comptes de tiers
491 Dépréciations des comptes de clients [Passif]
495 Dépréciations des comptes du groupe et des associés [Passif]
4951 Comptes du groupe
4955 Comptes courants des associés
4958 Opérations faites en commun et en GIE
496 Dépréciations des comptes de débiteurs divers [Passif]
4962 Créances sur cessions d'immobilisations
4965 Créances sur cessions de valeurs mobilières de placement
4967 Autres comptes débiteurs

Classe 5 — Comptes financiers
50 Valeurs mobilières de placement
502 Actions propres
5021 Actions destinées à être attribuées aux employés et affectées à des plans déterminés
5022 Actions disponibles pour être attribuées aux employés ou pour la régularisation des cours de bourse
503 Actions
5031 Titres cotés
5035 Titres non cotés
504 Autres titres conférant un droit de propriété
505 Obligations et bons émis par la société et rachetés par elle
506 Obligations
5061 Titres cotés
5065 Titres non cotés
507 Bons du Trésor et bons de caisse à court terme
508 Autres valeurs mobilières de placement et autres créances assimilées
5081 Autres valeurs mobilières
5082 Bons de souscription
5088 Intérêts courus sur obligations, bons et valeurs assimilés
509 Versements restant à effectuer sur valeurs mobilières de placement non libérées
51 Banques, établissements financiers et assimilés
511 Valeurs à l'encaissement [Actif ou passif]
5111 Coupons échus à l'encaissement
5112 Chèques à encaisser
5113 Effets à l'encaissement
5114 Effets à l'escompte
512 Banques [Actif ou passif]
518 Intérêts courus [Actif ou passif]
5181 Intérêts courus à payer
5188 Intérêts courus à recevoir
519 Concours bancaires courants [Actif ou passif]
5191 Crédit de mobilisation de créances commerciales
5193 Mobilisation de créances nées à l'étranger
5198 Intérêts courus sur concours bancaires courants
52 Instruments financiers à terme et jetons détenus [Actif ou passif]
521 Instruments financiers à terme
522 Jetons détenus
523 Jetons auto-détenus
524 Jetons empruntés
53 Caisse [Actif ou passif]
530 Caisse
58 Virements internes [Actif ou passif]
59 Dépréciations des comptes financiers [Passif]
590 Dépréciations des valeurs mobilières de placement [Passif]
5903 Actions
5904 Autres titres conférant un droit de propriété
5906 Obligations
5908 Autres valeurs mobilières de placement et créances assimilées

Classe 6 — Comptes de charges
60 Achats
601 Achats stockés - Matières premières et fournitures
602 Achats stockés - Autres approvisionnements
6021 Matières consommables
6022 Fournitures consommables
60221 Combustibles
60222 Produits d'entretien
60223 Fournitures d'atelier et d'usine
60224 Fournitures de magasin
60225 Fourniture de bureau
6026 Emballages
60261 Emballages perdus
60262 Malis sur emballage
60265 Emballages récupérables non identifiables
60267 Emballages à usage mixte
604 Achats d'études et prestations de services
605 Achats de matériel, équipements et travaux
606 Achats non stockés de matière et fournitures
6061 Fournitures non stockables (eau, énergie, etc.)
6063 Fournitures d'entretien et de petit équipement
6064 Fournitures administratives
6068 Autres matières et fournitures
607 Achats de marchandises
608 Regroupement des frais accessoires incorporés aux achats
609 Rabais, remises et ristournes obtenus sur achats
6098 RRR non affectés
603 Variation des stocks d'approvisionnements et de marchandises
6031 Variation des stocks de matières premières et fournitures
6032 Variation des stocks des autres approvisionnements
6037 Variation des stocks de marchandises
61 Services extérieurs
611 Sous-traitance générale
612 Redevances de crédit-bail
6122 Crédit-bail mobilier
6125 Crédit-bail immobilier
613 Locations
6132 Locations immobilières
6135 Locations mobilières
614 Charges locatives et de copropriété
615 Entretien et réparation
6152 Entretien et réparation sur biens immobiliers
6155 Entretien et réparation sur biens mobiliers
6156 Maintenance
616 Primes d'assurances
6161 Multirisques
6162 Assurance obligatoire dommage construction
6163 Assurance - transport
61636 sur achats
61637 sur ventes
61638 sur autres biens
6164 Risques d'exploitation
6165 Insolvabilité clients
617 Études et recherches
618 Divers
6181 Documentation générale
6183 Documentation technique
6185 Frais de colloques, séminaires, conférences
619 Rabais, remises et ristournes obtenus sur services extérieurs
62 Autres services extérieurs
621 Personnel extérieur à l'entité
6211 Personnel intérimaire
6214 Personnel détaché ou prêté à l'entité
622 Rémunérations d'intermédiaires et honoraires
6221 Commissions et courtages sur achats
6222 Commissions et courtages sur ventes
6224 Rémunérations des transitaires
6225 Rémunérations d'affacturage
6226 Honoraires
6227 Frais d'actes et de contentieux
6228 Divers
623 Publicité, publications, relations publiques
6231 Annonces et insertions
6232 Échantillons
6233 Foires et expositions
6234 Cadeaux à la clientèle
6235 Primes
6236 Catalogues et imprimés
6237 Publications
6238 Divers (pourboires, dons courants)
624 Transports de biens et transports collectifs du personnel
6241 Transports sur achats
6242 Transports sur ventes
6243 Transports entre établissements ou chantiers
6244 Transports administratifs
6247 Transports collectifs du personnel
6248 Divers
625 Déplacements, missions et réceptions
6251 Voyages et déplacements
6255 Frais de déménagement
6256 Missions
6257 Réceptions
626 Frais postaux et de télécommunications
627 Services bancaires et assimilés
6271 Frais sur titres (achat, vente, garde)
6272 Commissions et frais sur émission d'emprunts
6275 Frais sur effets
6276 Location de coffres
6278 Autres frais et commissions sur prestations de services
628 Divers
6281 Concours divers (cotisations)
6284 Frais de recrutement de personnel
629 Rabais, remises et ristournes obtenus sur autres services extérieurs
63 Impôts, taxes et versements assimilés
631 Impôts, taxes et versements assimilés sur rémunérations (administrations des impôts)
6311 Taxe sur les salaires
6314 Cotisation pour défaut d'investissement obligatoire dans la construction
6318 Autres
633 Impôts, taxes et versements assimilés sur rémunérations (autres organismes)
6331 Versement de transport
6332 Allocations logement
6333 Contribution unique des employeurs à la formation professionnelle
6334 Participation des employeurs à l'effort de construction
6335 Versements libératoires ouvrant droit à l'exonération de la taxe d'apprentissage
6338 Autres
635 Autres impôts, taxes et versements assimilés (administrations des impôts)
6351 Impôts directs (sauf impôts sur les bénéfices)
63511 Contribution économique territoriale
63512 Taxes foncières
63513 Autres impôts locaux
63514 Taxe sur les véhicules des sociétés
6352 Taxe sur le chiffre d'affaires non récupérables
6353 Impôts indirects
6354 Droits d'enregistrement et de timbre
63541 Droits de mutation
6358 Autres droits
637 Autres impôts, taxes et versements assimilés (autres organismes)
6371 Contribution sociale de solidarité à la charge des sociétés
6372 Taxes perçues par les organismes publics internationaux
6374 Impôts et taxes exigibles à l'étranger
6378 Taxes diverses
638 Rappel d’impôts (autres qu’impôts sur les bénéfices)
64 Charges de personnel
641 Rémunérations du personnel
6411 Salaires, appointements
6412 Congés payés
6413 Primes et gratifications
6414 Indemnités et avantages divers
6415 Supplément familial
644 Rémunération du travail de l'exploitant
645 Cotisations de sécurité sociale et de prévoyance
6451 Cotisations à l'Urssaf
6452 Cotisations aux mutuelles
6453 Cotisations aux caisses de retraites
6454 Cotisations à Pôle emploi
6458 Cotisations aux autres organismes sociaux
646 Cotisations sociales personnelles de l'exploitant
647 Autres cotisations sociales
6471 Prestations directes
6472 Versements au comité social et économique
6474 Versements aux autres œuvres sociales
6475 Médecine du travail, pharmacie
648 Autres charges de personnel
649 Remboursements de charges de personnel
65 Autres charges de gestion courante
651 Redevances pour concessions, brevets, licences, marques, procédés, solutions informatiques, droits et valeurs similaires
6511 Redevances pour concessions, brevets, licences, marques, procédés, solutions informatiques
6516 Droits d'auteur et de reproduction
6518 Autres droits et valeurs similaires
653 Rémunérations de l’activité des administrateurs et des gérants
654 Pertes sur créances irrécouvrables
6541 Créances de l'exercice
6544 Créances des exercices antérieurs
655 Quote-part de résultat sur opérations faites en commun
6551 Quote-part de bénéfice transférée - comptabilité du gérant
6555 Quote-part de perte supportée - comptabilité des associés non gérants
656 Pertes de change sur créances et dettes commerciales
657 Valeurs comptables des immobilisations incorporelles et corporelles cédées
658 Pénalités et autres charges
6581 Pénalités sur marchés (et dédits payés sur achats et ventes)
6582 Pénalités, amendes fiscales et pénales
6583 Malis provenant de clauses d’indexation
6584 Lots
6588 Opérations de constitution ou liquidation des fiducies
66 Charges financières
661 Charges d'intérêts
6611 Intérêts des emprunts et dettes
66116 Intérêts des emprunts et dettes assimilées
66117 Intérêts des dettes rattachées à des participations
6612 Charges de la fiducie, résultat de la période
6615 Intérêts des comptes courants et des dépôts créditeurs
6616 Intérêts bancaires et sur opérations de financement (escompte…)
6617 Intérêts des obligations cautionnées
6618 Intérêts des autres dettes
66181 Intérêts des dettes commerciales
66188 Intérêts des dettes diverses
664 Pertes sur créances liées à des participations
665 Escomptes accordés
666 Pertes de change financières
667 Charges sur cession d’éléments financiers
6671 Valeurs comptables des immobilisations financières cédées
6672 Charges nettes sur cessions de titres immobilisés de l’activité de portefeuille
6673 Charges nettes sur cessions de valeurs mobilières de placement
6674 Charges nettes sur cessions de jetons
668 Autres charges financières
6683 Mali provenant du rachat par l’entité d’actions et obligations émises par elle-même
67 Charges exceptionnelles
672 Charges sur exercices antérieurs
678 Autres charges exceptionnelles
68 Dotations aux amortissements, aux dépréciations et aux provisions
681 Dotations aux amortissements, aux dépréciations et aux provisions
6811 Dotations aux amortissements sur immobilisations incorporelles et corporelles
68111 Immobilisations incorporelles et frais d’établissement
68112 Immobilisations corporelles
6815 Dotations aux provisions d'exploitation
6816 Dotations pour dépréciations des immobilisations incorporelles et corporelles
68161 Immobilisations incorporelles
68162 Immobilisations corporelles
6817 Dotations pour dépréciations des actifs circulants
68173 Stocks et en-cours
68174 Créances
686 Dotations aux amortissements, aux dépréciations et aux provisions
6861 Dotations aux amortissements des primes de remboursement des emprunts
6862 Dotations aux amortissements des frais d'émission des emprunts
6865 Dotations aux provisions financières
6866 Dotations pour dépréciation des éléments financiers
68662 Immobilisations financières
68665 Valeurs mobilières de placement
687 Dotations aux amortissements, aux dépréciations et aux provisions
6871 Dotations aux amortissements exceptionnels des immobilisations
6872 Dotations aux provisions réglementées (immobilisations)
68725 Amortissements dérogatoires
6873 Dotations aux provisions réglementées (stocks)
6874 Dotations aux autres provisions réglementées
6875 Dotations aux provisions exceptionnelles
6876 Dotations pour dépréciations exceptionnelles
69 Participation des salariés - Impôts sur les bénéfices et assimilés
691 Participation des salariés aux résultats
695 Impôts sur les bénéfices
6951 Impôts dus en France
6952 Contribution additionnelle à l'impôt sur les bénéfices
6954 Impôts dus à l'étranger
696 Suppléments d'impôt sur les sociétés liés aux distributions
698 Intégration fiscale
6981 Intégration fiscale - Charges
6989 Intégration fiscale - Produits
699 Produits - Reports en arrière des déficits

Classe 7 — Comptes de produits
70 Ventes de produits fabriqués, prestations de services, marchandises
701 Ventes de produits finis
702 Ventes de produits intermédiaires
703 Ventes de produits résiduels
704 Travaux
705 Études
706 Prestations de services
707 Ventes de marchandises
708 Produits des activités annexes
7081 Produits des services exploités dans l'intérêt du personnel
7082 Commissions et courtages
7083 Locations diverses
7084 Mise à disposition de personnel facturée
7085 Ports et frais accessoires facturés
7086 Bonis sur reprises d'emballages consignés
7087 Bonifications obtenues des clients et primes sur ventes
7088 Autres produits d'activités annexes (cessions d'approvisionnements)
709 Rabais, remises et ristournes accordés
7091 RRR accordés sur ventes de produits finis
7092 RRR accordés sur ventes de produits intermédiaires
7094 RRR accordés sur travaux
7095 RRR accordés sur études
7096 RRR accordés sur prestations de services
7097 RRR accordés sur ventes de marchandises
7098 RRR accordés sur produits des activités annexes
71 Production stockée (ou déstockage)
713 Variation des stocks des en-cours de production et de produits
7133 Variation des en-cours de production de biens
71331 Produits en cours
71335 Travaux en cours
7134 Variation des en-cours de production de services
71341 Études en cours
71345 Prestations de services en cours
7135 Variation des stocks de produits
71351 Produits intermédiaires
71355 Produits finis
71358 Produits résiduels
72 Production immobilisée
721 Immobilisations incorporelles
722 Immobilisations corporelles
74 Subventions
741 Subventions d’exploitation
742 Subventions d’équilibre
747 Quote-part des subventions d’investissement virée au résultat de l’exercice
75 Autres produits de gestion courante
751 Redevances pour concessions, brevets, licences, marques, procédés, solutions informatiques, droits et valeurs similaires
7511 Redevances pour concessions, brevets, licences, marques, procédés, solutions informatiques
7516 Droits d'auteur et de reproduction
7518 Autres droits et valeurs similaires
752 Revenus des immeubles non affectés à des activités professionnelles
753 Rémunérations de l’activité des administrateurs et des gérants
754 Ristournes perçues des coopératives provenant des excédents
755 Quote-part de résultat sur opérations faites en commun
7551 Quote-part de perte transférée - comptabilité du gérant
7555 Quote-part de bénéfice attribuée - comptabilité des associés non-gérants
756 Gains de change sur créances et dettes commerciales
757 Produits des cessions d’immobilisations incorporelles et corporelles
758 Indemnités et autres produits
7581 Dédits et pénalités perçus sur achats et ventes
7582 Libéralités reçues
7583 Rentrées sur créances amorties
7584 Dégrèvements d’impôts autres qu’impôts sur les bénéfices
7585 Bonis provenant de clauses d’indexation
7586 Lots
7587 Indemnités d’assurance
7588 Opérations de constitution ou liquidation des fiducies
76 Produits financiers
761 Produits de participations
7611 Revenus des titres de participation
7612 Produits de la fiducie, résultat de la période
7616 Revenus sur autres formes de participation
7617 Revenus des créances rattachées à des participations
762 Produits des autres immobilisations financières
7621 Revenus des titres immobilisés
7626 Revenus des prêts
7627 Revenus des créances immobilisées
763 Revenus des autres créances
7631 Revenus des créances commerciales
7638 Revenus des créances diverses
764 Revenus des valeurs mobilières de placement
765 Escomptes obtenus
766 Gains de change financiers
767 Produits sur cession d’éléments financiers
7671 Produits des cessions d’immobilisations financières
7672 Produits nets sur cessions de titres immobilisés de l’activité de portefeuille
7673 Produits nets sur cessions de valeurs mobilières de placement
7674 Produits nets sur cessions de jetons
768 Autres produits financiers
7683 Bonis provenant du rachat par l’entreprise d’actions et d’obligations émises par elle-même
77 Produits exceptionnels
772 Produits sur exercices antérieurs
778 Autres produits exceptionnels
78 Reprises sur amortissements, dépréciations et provisions
781 Reprises sur amortissements, dépréciations et provisions
7811 Reprises sur amortissements des immobilisations incorporelles et corporelles
78111 Immobilisations incorporelles
78112 Immobilisations corporelles
7815 Reprises sur provisions d'exploitation
7816 Reprises sur dépréciations des immobilisations incorporelles et corporelles
78161 Immobilisations incorporelles
78162 Immobilisations corporelles
7817 Reprises sur dépréciations des actifs circulants
78173 Stocks et en-cours
78174 Créances
786 Reprises sur dépréciations et provisions
7865 Reprises sur provisions financières
7866 Reprises sur dépréciations des éléments financiers
78662 Immobilisations financières
78665 Valeurs mobilières de placement
787 Reprises sur dépréciations et provisions
7872 Reprises sur provisions réglementées (immobilisations)
78725 Amortissements dérogatoires
7873 Reprises sur provisions réglementées (stocks)
7874 Reprises sur autres provisions réglementées
7875 Reprises sur provisions exceptionnelles
7876 Reprises sur dépréciations exceptionnelles

Classe 8 — Comptes spéciaux
89 Comptes de bilan
890 Bilan d'ouverture
891 Bilan de clôture

EOF_PLAN;

$plan = preg_replace("/\r/", '', $plan);
$plan = preg_replace("/\n{2,}/", "\n", $plan);
$src = explode("\n", $plan);


fputcsv(STDOUT, ['code','label','description','position','bookmark'], ',', '"', '\\');

foreach ($src as $line)
{
	$line = trim($line);

	if (empty($line)) {
		continue;
	}

	if (preg_match('!^(\d+)\s+(.+)$!', $line, $match))
	{
		$code = (int)$match[1];
		$nom = trim($match[2]);
		$parent = (int)substr($match[1], 0, -1);
	}
	elseif (preg_match('!^Classe (\d+)\s+.*$!i', $line, $match))
	{
		$code = (int)$match[1];
		$nom = trim($match[0]);
		$parent = 0;
	}
	else
	{
		echo "$line\n";
		continue;
	}

	if (preg_match('/^(.+?)\s+\[(Actif|Passif|Actif ou passif)\]\s*$/i', $nom, $match)) {
		$position = $match[2];
		$nom = $match[1];
	}
	else {
		$position = null;
	}

	$classe = substr((string)$code, 0, 1);

	if ($classe == 1) {
		$position ??= 'Passif';
	}
	elseif ($classe == 2 || $classe == 3 || $classe == 5) {
		$position ??= 'Actif';
	}
	// Comptes de classe 4, c'est compliqué là
	elseif ($classe == 4) {
		if ($position === null) {
			$position ??= 'Actif ou passif';
		}
	}
	elseif ($classe == 6) {
		$position ??= 'Charge';
	}
	elseif ($classe == 7) {
		$position ??= 'Produit';
	}
	elseif ($classe == 8) {
		if (substr($code, 0, 2) == 86) {
			$position ??= 'Charge';
		}
		elseif (substr($code, 0, 2) == 87) {
			$position ??= 'Produit';
		}
		else {
			$position ??= 'Actif ou passif';
		}
	}

	$position ??= '';

	fputcsv(STDOUT, [$code, trim($nom), '', $position, ''], ',', '"', '\\');
}

