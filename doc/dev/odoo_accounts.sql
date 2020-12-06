-- Schéma Odoo (PgSQL) pour info sur la compta
-- Pour les écritures, deux tables : move et move_line
-- Utilisation de deux colonnes "debit" et "credit"

-- https://github.com/odoo/odoo/blob/11.0/addons/account/data/data_account_type.xml
-- https://www.odoo.com/documentation/11.0/webservices/localization.html
CREATE TABLE public.account_account_type (
    id integer NOT NULL,
    name character varying NOT NULL,
    include_initial_balance boolean,
    type character varying NOT NULL,
    note text,
    create_uid integer,
    create_date timestamp without time zone,
    write_uid integer,
    write_date timestamp without time zone
);

COPY public.account_account_type (id, name, include_initial_balance, type, note, create_uid, create_date, write_uid, write_date) FROM stdin;
1	Receivable	t	receivable	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
2	Payable	t	payable	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
3	Bank and Cash	t	liquidity	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
4	Credit Card	t	liquidity	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
5	Current Assets	t	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
6	Non-current Assets	t	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
7	Prepayments	t	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
8	Fixed Assets	t	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
9	Current Liabilities	t	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
10	Non-current Liabilities	t	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
11	Equity	t	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
12	Current Year Earnings	t	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
13	Other Income	f	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
14	Income	f	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
15	Depreciation	f	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
16	Expenses	f	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
17	Cost of Revenue	f	other	\N	1	2019-02-14 15:05:35.697512	1	2019-02-14 15:05:35.697512
\.


COPY public.account_account (id, name, currency_id, code, deprecated, user_type_id, internal_type, last_time_entries_checked, reconcile, note, company_id, group_id, create_uid, create_date, write_uid, write_date) FROM stdin;
1	Virements Internes	\N	580000	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
2	Capital souscrit - non appelé	\N	101100	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
3	Capital souscrit - appelé non versé	\N	101200	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
4	Capital non amorti	\N	101310	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
5	Capital amorti	\N	101320	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
6	Capital souscrit soumis à des réglementations particulières	\N	101800	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
7	Primes d'émission	\N	104100	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
8	Primes de fusion	\N	104200	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
10	Primes de conversion d'obligations en actions	\N	104400	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
11	Bons de souscription d'actions	\N	104500	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
12	Réserve spéciale de réévaluation	\N	105100	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
13	Écart de réévaluation libre	\N	105200	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
15	Écarts de réévaluation (autres opérations légales)	\N	105500	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
16	Autres écarts de réévaluation en France	\N	105700	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
17	Autres écarts de réévaluation à l'étranger	\N	105800	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
18	Réserve légale proprement dite	\N	106110	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
19	Plus-values nettes à long terme	\N	106120	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
20	Réserves indisponibles	\N	106200	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
22	Plus-values nettes à long terme	\N	106410	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
23	Réserves consécutives à l'octroi de subventions d'investissement	\N	106430	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
24	Autres réserves réglementées	\N	106480	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
25	Réserve de propre assureur	\N	106810	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
26	Réserves diverses	\N	106880	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
27	Écarts d'équivalence	\N	107000	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
28	Compte de l'exploitant	\N	108000	f	11	other	\N	f	Capital pour une Entreprise Individuelle	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
29	Actionnaires : capital souscrit - non appelé	\N	109000	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
31	Report à nouveau (solde débiteur)	\N	119000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
30	Report à nouveau (solde créditeur)	\N	110000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
9	Primes d'apport	\N	104300	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
14	Réserve de réévaluation	\N	105300	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
21	Réserves statutaires ou contractuelles	\N	106300	f	11	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
32	Résultat de l'exercice (bénéfice)	\N	120000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
33	Résultat de l'exercice (perte)	\N	129000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
34	Subventions d'équipement - État	\N	131100	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
35	Subventions d'équipement - Régions	\N	131200	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
37	Subventions d'équipement - Communes	\N	131400	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
38	Subventions d'équipement - Collectivités publiques	\N	131500	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
39	Subventions d'équipement - Entreprises publiques	\N	131600	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
40	Subventions d'équipement - Entreprises et organismes privés	\N	131700	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
41	Subventions d'équipement - Autres	\N	131800	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
42	Autres subventions d'investissement (même ventilation que celle du compte 131)	\N	138000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
43	Subventions d'équipement inscrites au compte de résultat - État	\N	139110	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
44	Subventions d'équipement inscrites au compte de résultat - Régions	\N	139120	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
45	Subventions d'équipement inscrites au compte de résultat - Départements	\N	139130	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
46	Subventions d'équipement inscrites au compte de résultat - Communes	\N	139140	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
47	Subventions d'équipement inscrites au compte de résultat - Collectivités publiques	\N	139150	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
48	Subventions d'équipement inscrites au compte de résultat - Entreprises publiques	\N	139160	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
49	Subventions d'équipement inscrites au compte de résultat - Entreprises et organismes privés	\N	139170	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
50	Subventions d'équipement inscrites au compte de résultat - Autres	\N	139180	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
51	Autres subventions d'investissement (même ventilation que celle du compte 1391)	\N	139800	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
53	Provisions pour investissement (participation des salariés)	\N	142400	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
54	Provisions réglementées relatives aux stocks - Hausse de prix	\N	143100	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
55	Provisions réglementées relatives aux stocks - Fluctuation des cours	\N	143200	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
56	Provisions réglementées relatives aux autres éléments de l'actif	\N	144000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
57	Amortissements dérogatoires	\N	145000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
58	Provision spéciale de réévaluation	\N	146000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
36	Subventions d'équipement - Départements	\N	131300	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
52	Provisions reconstitution des gisements miniers et pétroliers	\N	142300	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
719	Bla bla	\N	512002	f	3	liquidity	\N	f	\N	1	\N	1	2019-02-14 15:11:18.063543	1	2019-02-14 15:11:18.063543
59	Plus-values réinvesties	\N	147000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
60	Autres provisions réglementées	\N	148000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
61	Provisions pour litiges	\N	151100	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
62	Provisions pour garanties données aux clients	\N	151200	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
64	Provisions pour amendes et pénalités	\N	151400	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
65	Provisions pour pertes de change	\N	151500	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
66	Provisions pour pertes sur contrats	\N	151600	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
67	Autres provisions pour risques	\N	151800	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
69	Provisions pour restructurations	\N	154000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
70	Provisions pour impôts	\N	155000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
71	Provisions pour renouvellement des immobilisations (entreprises concessionnaires)	\N	156000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
72	Provisions pour charges à répartir sur plusieurs exercices - Gros entretien ou grandes révisions	\N	157200	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
73	Provisions pour remises en état	\N	158100	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
76	Emprunts auprès des établissements de crédit	\N	164000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
77	Dépôts	\N	165100	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
78	Cautionnements	\N	165500	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
79	Participation des salariés aux résultats - Comptes bloqués	\N	166100	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
80	Participation des salariés aux résultats - Fonds de participation	\N	166200	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
81	Emprunts et dettes assortis de conditions particulières - Emissions de titres participatifs	\N	167100	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
82	Emprunts et dettes assortis de conditions particulières - Avances conditionnées de l'État	\N	167400	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
83	Emprunts et dettes assortis de conditions particulières - Emprunts participatifs	\N	167500	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
84	Autres emprunts et dettes assimilées - Autres emprunts	\N	168100	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
85	Autres emprunts et dettes assimilées - Rentes viagères capitalisées	\N	168500	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
86	Autres emprunts et dettes assimilées - Autres dettes	\N	168700	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
74	Emprunts obligataires convertibles	\N	161000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
63	Provisions pour pertes sur marchés à terme	\N	151300	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
68	Provisions pour pensions et obligations similaires	\N	153000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
75	Autres emprunts obligataires	\N	163000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
87	Intérêts courus sur emprunts obligataires convertibles	\N	168810	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
88	Intérêts courus sur autres emprunts obligataires	\N	168830	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
89	Intérêts courus sur emprunts auprès des établissements de crédit	\N	168840	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
90	Intérêts courus sur dépôts et cautionnements reçus	\N	168850	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
91	Intérêts courus sur participation des salariés aux résultats	\N	168860	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
92	Intérêts courus sur emprunts et dettes assortis de conditions particulières	\N	168870	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
93	Intérêts courus sur autres emprunts et dettes assimilées	\N	168880	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
94	Primes de remboursement des obligations	\N	169000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
96	Dettes rattachées à des participations (hors groupe)	\N	174000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
97	Dettes rattachées à des sociétés en participation - Principal	\N	178100	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
98	Dettes rattachées à des sociétés en participation - Intérêts courus	\N	178800	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
100	Biens et prestations de services échangés entre établissements (charges)	\N	186000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
101	Biens et prestations de services échangés entre établissements (produits)	\N	187000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
102	Comptes de liaison des sociétés en participation	\N	188000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
103	Immobilisations incorporelles - Frais d'établissement - Frais de constitution	\N	201100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
104	Immobilisations incorporelles - Frais d'établissement - Frais de prospection	\N	201210	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
105	Immobilisations incorporelles - Frais d'établissement - Frais de publicité	\N	201220	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
108	Immobilisations incorporelles - Concessions et droits similaires, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires	\N	205000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
109	Immobilisations incorporelles - Droit au bail	\N	206000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
110	Immobilisations incorporelles - Fonds commercial	\N	207000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
111	Autres immobilisations incorporelles	\N	208000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
95	Dettes rattachées à des participations (groupe)	\N	171000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
237	Études en cours E 1	\N	341100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
99	Comptes de liaison des établissements	\N	181000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
106	Immobilisations incorporelles - Frais d'augmentation de capital et d'opérations diverses (fusions, scissions, transformations)	\N	201300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
107	Immobilisations incorporelles - Frais de recherche et de développement	\N	203000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
112	Immobilisations corporelles - Terrains nus	\N	211100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
113	Immobilisations corporelles - Terrains aménagés	\N	211200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
115	Immobilisations corporelles - Carrières	\N	211410	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
116	Immobilisations corporelles - Terrains bâtis - Ensembles immobiliers industriels	\N	211510	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
117	Immobilisations corporelles - Terrains bâtis - Ensembles immobiliers administratifs et commerciaux	\N	211550	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
118	Immobilisations corporelles - Terrains bâtis affectés aux opérations professionnelles	\N	211581	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
119	Immobilisations corporelles - Terrains bâtis affectés aux opérations non professionnelles	\N	211588	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
120	Immobilisations corporelles - Compte d'ordre sur immobilisations	\N	211600	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
121	Immobilisations corporelles - Agencements et aménagements de terrains (même ventilation que celle du compte 211)	\N	212000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
122	Immobilisations corporelles - Bâtiments - Ensembles immobiliers industriels	\N	213110	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
123	Immobilisations corporelles - Bâtiments - Ensembles immobiliers administratifs et commerciaux	\N	213150	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
124	Immobilisations corporelles - Bâtiments affectés aux opérations professionnelles	\N	213181	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
125	Immobilisations corporelles - Bâtiments affectés aux opérations non professionnelles	\N	213188	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
126	Immobilisations corporelles - Installations générales, agencements, aménagements des constructions (même ventilation que celle du compte 2131)	\N	213500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
127	Immobilisations corporelles - Ouvrages d'infrastructure - Voies de terre	\N	213810	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
128	Immobilisations corporelles - Ouvrages d'infrastructure - Voies de fer	\N	213820	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
129	Immobilisations corporelles - Ouvrages d'infrastructure - Voies d'eau	\N	213830	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
130	Immobilisations corporelles - Ouvrages d'infrastructure - Barrages	\N	213840	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
131	Immobilisations corporelles - Ouvrages d'infrastructure - Pistes d'aérodromes	\N	213850	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
132	Immobilisations corporelles - Constructions sur sol d'autrui (même ventilation que celle du compte 213)	\N	214000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
133	Immobilisations corporelles - Installations complexes spécialisées sur sol propre	\N	215110	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
134	Immobilisations corporelles - Installations complexes spécialisées sur sol d'autrui	\N	215140	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
114	Immobilisations corporelles - Sous-sols et sur-sols	\N	211300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
135	Immobilisations corporelles - Installations à caractère spécifique sur sol propre	\N	215310	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
136	Immobilisations corporelles - Installations à caractère spécifique sur sol d'autrui	\N	215340	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
137	Immobilisations corporelles - Matériels industriels	\N	215400	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
138	Immobilisations corporelles - Outillage industriel	\N	215500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
139	Immobilisations corporelles - Agencements et aménagements des matériels et outillage industriels	\N	215700	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
140	Immobilisations corporelles - Installations générales agencements aménagements divers	\N	218100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
141	Immobilisations corporelles - Matériel de transport	\N	218200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
143	Immobilisations corporelles - Mobilier	\N	218400	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
144	Immobilisations corporelles - Cheptel	\N	218500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
145	Immobilisations corporelles - Emballages récupérables	\N	218600	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
146	Immobilisations mises en concession	\N	220000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
147	Immobilisations corporelles en cours - Terrains	\N	231200	f	5	other	\N	f	Pas d'amortissement sur les terrains	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
149	Immobilisations corporelles en cours - Installations techniques matériel et outillage industriels	\N	231500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
150	Autres immobilisations corporelles en cours	\N	231800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
151	Immobilisations incorporelles en cours	\N	232000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
152	Avances et acomptes versés sur commandes d'immobilisations incorporelles	\N	237000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
153	Avances et acomptes versés sur commandes d'immobilisations corporelles - Terrains	\N	238200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
155	Avances et acomptes versés sur commandes d'immobilisations corporelles - Installations techniques matériel et outillage industriels	\N	238500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
156	Avances et acomptes versés sur commandes d'immobilisations corporelles - Autres immobilisations corporelles	\N	238800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
157	Parts dans des entreprises liées et créances sur des entreprises liées	\N	250000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
158	Titres de participation - Actions	\N	261100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
159	Autres titres de participation	\N	261800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
142	Immobilisations corporelles - Matériel de bureau et matériel informatique	\N	218300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
148	Immobilisations corporelles en cours - Constructions	\N	231300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
154	Avances et acomptes versés sur commandes d'immobilisations corporelles - Constructions	\N	238300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
160	Titres évalués par équivalence	\N	262000	f	5	other	\N	f	Pas d'amortissement sur les titres évalués par équivalence	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
161	Autres formes de participation	\N	266000	f	5	other	\N	f	Pas d'amortissement sur les titres évalués par équivalence	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
162	Créances rattachées à des participations (groupe)	\N	267100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
163	Créances rattachées à des participations (hors groupe)	\N	267400	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
164	Versements représentatifs d'apports non capitalisés (appel de fonds)	\N	267500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
166	Autres créances rattachées à des participations	\N	267700	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
167	Créances rattachées à des participations - Intérêts courus	\N	267800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
168	Créances rattachées à des sociétés en participation - Principal	\N	268100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
169	Créances rattachées à des sociétés en participation - Intérêts courus	\N	268800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
170	Versements restant à effectuer sur titres de participation non libérés	\N	269000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
171	Titres immobilisés autres que les titres immobilisés de l'activité de portefeuille - Actions	\N	271100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
172	Titres immobilisés autres que les titres immobilisés de l'activité de portefeuille - Autres titres	\N	271800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
173	Titres immobilisés - Obligations	\N	272100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
174	Titres immobilisés - Bons	\N	272200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
176	Prêts participatifs	\N	274100	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
177	Prêts aux associés	\N	274200	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
179	Autres prêts	\N	274800	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
180	Dépôts	\N	275100	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
181	Cautionnements	\N	275500	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
182	Autres créances immobilisées - Créances diverses	\N	276100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
183	Autres créances immobilisées - Intérêts courus sur titres immobilisés (droits de créance)	\N	276820	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
184	Autres créances immobilisées - Intérêts courus sur prêts	\N	276840	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
185	Autres créances immobilisées - Intérêts courus sur dépôts et cautionnements	\N	276850	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
186	Autres créances immobilisées - Intérêts courus sur créances diverses	\N	276880	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
187	Actions propres ou parts propres	\N	277100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
175	Titres immobilisés de l'activité de portefeuille	\N	273000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
178	Prêts au personnel	\N	274300	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
165	Avances consolidables	\N	267600	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
188	Actions propres ou parts propres en voie d'annulation	\N	277200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
189	Versements restant à effectuer sur titres immobilisés non libérés	\N	279000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
190	Amortissements des immobilisations incorporelles - Frais d'établissement (même ventilation que celle du compte 201)	\N	280100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
192	Amortissements des immobilisations incorporelles - Concessions et droits similaires, brevets, licences, logiciels, droits et valeurs similaires	\N	280500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
193	Amortissements des immobilisations incorporelles - Fonds commercial	\N	280700	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
194	Amortissements des autres immobilisations incorporelles	\N	280800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
195	Amortissements des immobilisations corporelles - Terrains de gisement	\N	281100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
196	Amortissements des immobilisations corporelles - Agencements aménagements de terrains (même ventilation que celle du compte 212)	\N	281200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
198	Amortissements des immobilisations corporelles - Constructions sur sol d'autrui (même ventilation que celle du compte 214)	\N	281400	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
199	Amortissements des immobilisations corporelles - Installations matériel et outillage industriels (même ventilation que celle du compte 215)	\N	281500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
200	Amortissements des autres immobilisations corporelles (même ventilation que celle du compte 218)	\N	281800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
201	Amortissements des immobilisations mises en concession	\N	282000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
202	Dépréciations des immobilisations incorporelles - Marques, procédés, droits et valeurs similaires	\N	290500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
203	Dépréciations des immobilisations incorporelles - Droit au bail	\N	290600	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
204	Dépréciations des immobilisations incorporelles - Fonds commercial	\N	290700	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
205	Dépréciations des autres immobilisations incorporelles	\N	290800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
206	Dépréciations des immobilisations corporelles - Terrains (autres que terrains de gisement)	\N	291100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
207	Dépréciations des immobilisations mises en concession	\N	292000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
208	Dépréciations des immobilisations corporelles en cours	\N	293100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
209	Dépréciations des immobilisations incorporelles en cours	\N	293200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
191	Amortissements des immobilisations incorporelles - Frais de recherche et de développement	\N	280300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
197	Amortissements des immobilisations corporelles - Constructions (même ventilation que celle du compte 213)	\N	281300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
210	Provisions pour dépréciation des titres de participation	\N	296100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
211	Provisions pour dépréciation des autres formes de participation	\N	296600	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
212	Provisions pour dépréciation des créances rattachées à des participations (même ventilation que celle du compte 267)	\N	296700	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
213	Provisions pour dépréciation des créances rattachées à des sociétés en participation (même ventilation que celle du compte 268)	\N	296800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
214	Provisions pour dépréciation des titres immobilisés autres que les titres immobilisés de l'activité de portefeuille - droit de propriété (ventilation : 271)	\N	297100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
215	Provisions pour dépréciation des titres immobilisés - droit de créance (même ventilation que celle du compte 272)	\N	297200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
217	Provisions pour dépréciation des prêts (même ventilation que celle du compte 274)	\N	297400	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
218	Provisions pour dépréciation des dépôts et cautionnements versés (même ventilation que celle du compte 275)	\N	297500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
221	Matières premières (ou groupe) B	\N	312000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
222	Fournitures A, B, C, ..	\N	317000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
223	Matières consommables (ou groupe) C	\N	321100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
224	Matières consommables (ou groupe) D	\N	321200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
225	Combustibles	\N	322100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
226	Produits d'entretien	\N	322200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
228	Fournitures de magasin	\N	322400	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
229	Fournitures de bureau	\N	322500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
230	Emballages perdus	\N	326100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
231	Emballages récupérables non identifiables	\N	326500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
232	Emballages à usage mixte	\N	326700	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
233	Produit en cours P 1	\N	331100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
234	Produit en cours P 2	\N	331200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
235	Travaux en cours T 1	\N	335100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
236	Travaux en cours T 2	\N	335200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
220	Matières premières (ou groupe) A	\N	311000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
216	Provisions pour dépréciation des titres immobilisés de l'activité de portefeuille	\N	297300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
227	Fournitures d'atelier et d usine	\N	322300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
219	Provisions pour dépréciation des autres créances immobilisées (même ventilation que celle du compte 276)	\N	297600	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
238	Études en cours E 2	\N	341200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
239	Prestations de services en cours S 1	\N	345100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
240	Prestations de services en cours S 2	\N	345200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
241	Stocks produits intermédiaires (ou groupe) A	\N	351100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
242	Stocks produits intermédiaires (ou groupe) B	\N	351200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
243	Stocks produits finis (ou groupe) A	\N	355100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
244	Stocks produits finis (ou groupe) B	\N	355200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
245	Stocks produits résiduels - Déchets	\N	358100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
246	Stocks produits résiduels - Rebuts	\N	358500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
247	Stocks produits résiduels - Matières de récupération	\N	358600	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
248	Stocks provenant d'immobilisations	\N	360000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
250	Stocks de marchandises (ou groupe) B	\N	372000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
251	Stocks en voie d'acheminement	\N	380000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
252	Provisions pour dépréciation des matières premières (ou groupe) A	\N	391100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
253	Provisions pour dépréciation des matières premières (ou groupe) B	\N	391200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
254	Provisions pour dépréciation des fournitures A, B, C, ..	\N	391700	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
255	Provisions pour dépréciation des matières consommables (même ventilation que celle du compte 321)	\N	392100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
256	Provisions pour dépréciation des fournitures consommables (même ventilation que celle du compte 322)	\N	392200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
257	Provisions pour dépréciation des emballages (même ventilation que celle du compte 326)	\N	392600	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
258	Provisions pour dépréciation des produits en cours (même ventilation que celle du compte 331)	\N	393100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
259	Provisions pour dépréciation des travaux en cours (même ventilation que celle du compte 335)	\N	393500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
260	Provisions pour dépréciation des études en cours (même ventilation que celle du compte 341)	\N	394100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
261	Provisions pour dépréciation des prestations de services en cours (même ventilation que celle du compte 345)	\N	394500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
262	Provisions pour dépréciation des produits intermédiaires (même ventilation que celle du compte 351)	\N	395100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
263	Provisions pour dépréciation des produits finis (même ventilation que celle du compte 355)	\N	395500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
249	Stocks de marchandises (ou groupe) A	\N	371000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
264	Provisions pour dépréciation des stocks de marchandises (ou groupe) A	\N	397100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
265	Provisions pour dépréciation des stocks de marchandises (ou groupe) B	\N	397200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
266	Fournisseurs et comptes rattachés	\N	400000	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
267	Fournisseurs - Achats de biens et prestations de services	\N	401100	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
268	Fournisseurs - Retenues de garantie	\N	401700	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
270	Fournisseurs - Achats d'immobilisations	\N	404100	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
271	Fournisseurs d'immobilisations - Retenues de garantie	\N	404700	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
272	Fournisseurs d'immobilisations - Effets à payer	\N	405000	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
273	Factures non parvenues - Fournisseurs	\N	408100	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
274	Factures non parvenues - Fournisseurs d'immobilisations	\N	408400	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
275	Factures non parvenues - Fournisseurs - Intérêts courus	\N	408800	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
276	Fournisseurs débiteurs - Créances pour emballages et matériel à rendre	\N	409600	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
277	Fournisseurs débiteurs - Autres avoirs des fournisseurs d'exploitation	\N	409710	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
278	Fournisseurs débiteurs - Autres avoirs des fournisseurs d'immobilisations	\N	409740	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
279	Fournisseurs débiteurs - Rabais, remises, ristournes à obtenir et autres avoirs non encore reçus	\N	409800	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
281	Clients - Ventes de biens ou de prestations de services	\N	411100	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
282	Clients - Retenues de garantie	\N	411700	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
284	Clients douteux ou litigieux	\N	416000	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
285	Clients - Factures à établir	\N	418100	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
286	Clients - Intérêts courus non encore facturés	\N	418800	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
287	Clients créditeurs - Avances et acomptes reçus sur commandes	\N	419100	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
288	Clients créditeurs - Dettes pour emballages et matériels consignés	\N	419600	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
289	Clients créditeurs - Autres avoirs	\N	419700	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
290	Clients créditeurs - Rabais, remises, ristournes à accorder et autres avoirs à établir	\N	419800	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
280	Clients et comptes rattachés	\N	410000	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
269	Fournisseurs - Effets à payer	\N	403000	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
283	Clients - Effets à recevoir	\N	413000	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
292	Comités d'entreprise, d'établissement	\N	422000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
293	Participation des salariés aux résultats - Réserve spéciale	\N	424600	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
294	Participation des salariés aux résultats - Comptes courants	\N	424800	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
295	Personnel - Avances et acomptes	\N	425000	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
296	Personnel - Dépôts	\N	426000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
297	Personnel - Oppositions	\N	427000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
298	Personnel - Dettes provisionnées pour congés à payer	\N	428200	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
299	Personnel - Dettes provisionnées pour participation des salariés aux résultats	\N	428400	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
300	Personnel - Autres charges à payer	\N	428600	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
301	Personnel - Produits à recevoir	\N	428700	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
303	Autres organismes sociaux	\N	437000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
304	Charges sociales sur congés à payer	\N	438200	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
305	Organismes sociaux - Autres charges à payer	\N	438600	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
306	Organismes sociaux - Produits à recevoir	\N	438700	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
307	État - Subventions à recevoir - Subventions d'investissement	\N	441100	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
308	État - Subventions à recevoir - Subventions d'exploitation	\N	441700	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
309	État - Subventions à recevoir - Subventions d'équilibre	\N	441800	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
310	État - Subventions à recevoir - Avances sur subventions	\N	441900	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
311	État - Impôts et taxes recouvrables sur des tiers - Obligataires	\N	442400	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
312	État - Impôts et taxes recouvrables sur des tiers - Associés	\N	442500	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
313	Créances sur l'État résultant de la suppression de la règle du décalage d'un mois en matière de TVA	\N	443100	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
314	État - Intérêts courus sur créances figurant au compte 4431	\N	443800	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
315	État - Impôts sur les bénéfices	\N	444000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
316	TVA due intracommunautaire (Taux Normal)	\N	445201	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
317	TVA due intracommunautaire (Taux Intermédiaire)	\N	445202	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
318	TVA due intracommunautaire (Autre taux)	\N	445203	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
319	TVA due imports	\N	445204	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
291	Personnel - Rémunérations dues	\N	421000	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
302	Sécurité Sociale	\N	431000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
320	TVA à décaisser	\N	445510	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
321	Taxes assimilées à la TVA	\N	445580	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
322	TVA déductible sur immobilisations	\N	445620	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
323	TVA déductible transférée par d'autres entreprises	\N	445630	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
324	TVA déductible sur autres biens et services	\N	445660	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
325	TVA déductible intracommunautaire	\N	445662	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
326	TVA déductible imports	\N	445663	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
327	Crédit de TVA à reporter	\N	445670	f	5	other	\N	t	Si le remboursement n'a pas été demandé	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
328	Taxes déductibles assimilées à la TVA	\N	445680	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
329	TVA collectée (Taux Normal)	\N	445711	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
330	TVA collectée (Taux Intermédiaire)	\N	445712	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
331	TVA collectée (Autre taux)	\N	445713	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
332	Taxes collectées assimilées à la TVA	\N	445780	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
333	Taxes sur le chiffre d'affaires à régulariser ou en attente	\N	445800	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
334	Acomptes - Régime simplifié d'imposition	\N	445810	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
335	Acomptes - Régime du forfait	\N	445820	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
336	Remboursement de taxes sur le chiffre d'affaires demandé	\N	445830	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
337	TVA récupérée d'avance	\N	445840	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
338	Taxes sur le chiffre d'affaires sur factures non parvenues	\N	445860	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
339	Taxes sur le chiffre d'affaires sur factures à établir	\N	445870	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
340	Obligations cautionnées	\N	446000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
341	Autres impôts, taxes et versements assimilés	\N	447000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
342	État - Charges fiscales sur congés à payer	\N	448200	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
343	État - Charges à payer	\N	448600	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
344	État - Produits à recevoir	\N	448700	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
345	Quotas d'émission à restituer à l'État	\N	449000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
347	Associés - Comptes courants - Principal	\N	455100	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
348	Associés - Comptes courants - Intérêts courus	\N	455800	f	2	payable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
349	Associés - Comptes d'apport en société - Apports en nature	\N	456110	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
346	Groupe	\N	451000	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
350	Associés - Comptes d'apport en société - Apports en numéraire	\N	456150	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
351	Actionnaires - Capital souscrit et appelé, non versé	\N	456210	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
352	Associés - Capital appelé, non versé	\N	456250	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
354	Associés - Versements anticipés	\N	456400	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
355	Actionnaires défaillants	\N	456600	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
356	Associés - Capital à rembourser	\N	456700	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
357	Associés - Dividendes à payer	\N	457000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
358	Associés - Opérations faites en commun et en GIE - Opérations courantes	\N	458100	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
359	Associés - Opérations faites en commun et en GIE - Intérêts courus	\N	458800	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
360	Créances sur cessions d'immobilisations	\N	462000	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
361	Dettes sur acquisitions de valeurs mobilières de placement	\N	464000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
362	Créances sur cessions de valeurs mobilières de placement	\N	465000	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
363	Autres comptes débiteurs ou créditeurs	\N	467000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
364	Charges à payer	\N	468600	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
365	Produits à recevoir	\N	468700	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
367	Différence de conversion - Actif - Diminution des créances	\N	476100	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
368	Différence de conversion - Actif - Augmentation des dettes	\N	476200	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
369	Différence de conversion - Actif - Différences compensées par couverture de change	\N	476800	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
370	Différences de conversion - Passif - Augmentation des créances	\N	477100	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
371	Différences de conversion - Passif - Diminution des dettes	\N	477200	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
372	Différences de conversion - Passif - Différences compensées par couverture de change	\N	477800	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
373	Autres comptes transitoires	\N	478000	f	9	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
374	Charges à répartir sur plusieurs exercices - Frais d'émission des emprunts	\N	481600	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
375	Charges constatées d'avance	\N	486000	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
376	Produits constatés d'avance	\N	487000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
377	Comptes de répartition périodique des charges	\N	488600	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
366	Compte d'attente	\N	471000	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
353	Associés - Versements reçus sur augmentation de capital	\N	456300	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
378	Comptes de répartition périodique des produits	\N	488700	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
379	Quotas d'émission alloués par l'État	\N	489000	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
381	Provisions pour dépréciation des comptes du groupe	\N	495100	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
382	Provisions pour dépréciation des comptes courants des associés	\N	495500	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
383	Provisions pour dépréciation des opérations faites en commun et en GIE	\N	495800	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
384	Provisions pour dépréciation des créances sur cessions d'immobilisations	\N	496200	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
385	Provisions pour dépréciation des créances sur cessions de valeurs mobilières de placement	\N	496500	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
386	Provisions pour dépréciation - Autres comptes débiteurs	\N	496700	f	1	receivable	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
388	Valeurs mobilières de placement - Actions propres	\N	502000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
389	Valeurs mobilières de placement - Titres cotés	\N	503100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
390	Valeurs mobilières de placement - Titres non cotés	\N	503500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
391	Valeurs mobilières de placement - Autres titres conférant un droit de propriété	\N	504000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
392	Obligations et bons émis par la société et rachetés par elle	\N	505000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
393	Obligations cotés	\N	506100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
394	Obligations non cotés	\N	506500	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
395	Bons du Trésor et bons de caisse à court terme	\N	507000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
396	Autres valeurs mobilières de placement	\N	508100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
397	Bons de souscription	\N	508200	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
398	Intérêts courus sur obligations, bons et valeurs assimilées	\N	508800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
399	Versements restant à effectuer sur valeurs mobilières de placement non libérées	\N	509000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
400	Coupons échus à l'encaissement	\N	511100	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
401	Chèques à encaisser	\N	511200	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
403	Effets à l'escompte	\N	511400	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
404	Banques - Comptes en devises	\N	512400	f	3	liquidity	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
405	Chèques postaux	\N	514000	f	3	liquidity	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
380	Provisions pour dépréciation des comptes de clients	\N	491000	f	9	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
387	Valeurs mobilières de placement - Parts dans entreprises liées	\N	501000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
402	Effets à l'encaissement	\N	511300	f	5	other	\N	t	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
406	Caisses du Trésor et des établissements publics	\N	515000	f	3	liquidity	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
407	Sociétés de bourse	\N	516000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
408	Autres organismes financiers	\N	517000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
409	Intérêts courus à payer	\N	518100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
410	Intérêts courus à recevoir	\N	518800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
411	Concours bancaires courants - Crédit de mobilisation de créances commerciales (CMCC)	\N	519100	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
413	Concours bancaires courants - Intérêts courus sur concours bancaires courants	\N	519800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
414	Instruments de trésorerie	\N	520000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
415	Caisse en monnaie nationale	\N	531100	f	3	liquidity	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
416	Caisse en devises	\N	531400	f	3	liquidity	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
417	Caisse succursale (ou usine) A	\N	532000	f	3	liquidity	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
419	Régies d'avances et accréditifs	\N	540000	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
421	Provisions pour dépréciation des autres titres conférant un droit de propriété	\N	590400	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
422	Provisions pour dépréciation des obligations	\N	590600	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
423	Provisions pour dépréciation des autres valeurs mobilières de placement et créances assimilées (provisions)	\N	590800	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
424	Achats stockés - Matières premières (ou groupe) A	\N	601100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
425	Achats stockés - matières premières (ou groupe) B	\N	601200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
426	Achats stockés - Fournitures A, B, C, ..	\N	601700	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
427	Achats stockés - Matières consommables (ou groupe) C	\N	602110	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
428	Achats stockés - Matières consommables (ou groupe) D	\N	602120	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
429	Achats stockés - Combustibles	\N	602210	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
430	Achats stockés - Produits d'entretien	\N	602220	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
431	Achats stockés - Fournitures d'atelier et d'usine	\N	602230	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
432	Achats stockés - Fournitures de magasin	\N	602240	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
433	Achats stockés - Fournitures de bureau	\N	602250	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
434	Achats stockés - Emballages perdus	\N	602610	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
412	Concours bancaires courants - Mobilisation de créances nées à l'étranger	\N	519300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
418	Caisse succursale (ou usine) B	\N	533000	f	3	liquidity	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
420	Provisions pour dépréciation des actions	\N	590300	f	5	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
435	Achats stockés - Emballages récupérables non identifiables	\N	602650	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
436	Achats stockés - Emballages à usage mixte	\N	602670	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
437	Variation des stocks de matières premières (et fournitures)	\N	603100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
438	Variation des stocks des autres approvisionnements	\N	603200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
439	Variation des stocks de marchandises	\N	603700	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
440	Achats d'études et prestations de services	\N	604000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
441	Achats de matériel équipements et travaux	\N	605000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
442	Fournitures non stockables (eau, énergie...)	\N	606100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
444	Fournitures administratives	\N	606400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
445	Achats autres matières et fournitures	\N	606800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
446	Achats de marchandises (ou groupe) A	\N	607100	f	16	other	\N	f	Pour achats France	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
447	Achats de marchandises (ou groupe) B	\N	607200	f	16	other	\N	f	Pour déclaration TVA intracommunautaire	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
448	Frais accessoires incorporés aux achats	\N	608000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
449	Rabais, remises et ristournes obtenus sur achats de matières premières (et fournitures)	\N	609100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
450	Rabais, remises et ristournes obtenus sur achats d'autres approvisionnements stockés	\N	609200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
451	Rabais, remises et ristournes obtenus sur achats d'études et prestations de services	\N	609400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
452	Rabais, remises et ristournes obtenus sur achats de matériel, équipements et travaux	\N	609500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
453	Rabais, remises et ristournes obtenus sur achats d'approvisionnements non stockés	\N	609600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
454	Rabais, remises et ristournes obtenus sur achats de marchandises	\N	609700	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
455	Rabais, remises et ristournes non affectés	\N	609800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
457	Redevances de crédit-bail mobilier	\N	612200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
458	Redevances de crédit-bail immobilier	\N	612500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
459	Locations immobilières	\N	613200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
460	Locations mobilières	\N	613500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
461	Locations malis sur emballages	\N	613600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
462	Charges locatives et de copropriété	\N	614000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
456	Sous-traitance générale	\N	611000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
443	Fournitures d'entretien et de petit équipement	\N	606300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
463	Entretien et réparations sur biens immobiliers	\N	615200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
464	Entretien et réparations sur biens mobiliers	\N	615500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
465	Maintenance	\N	615600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
466	Assurance multirisques	\N	616100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
467	Assurance obligatoire dommage construction	\N	616200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
468	Assurance transport sur achats	\N	616360	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
469	Assurance transport sur ventes	\N	616370	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
470	Assurance transport sur autres biens	\N	616380	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
471	Assurance risques d'exploitation	\N	616400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
472	Assurance insolvabilité clients	\N	616500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
473	Études et recherches	\N	617000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
474	Documentation générale	\N	618100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
476	Frais de colloques, séminaires, conférences	\N	618500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
477	Rabais, remises et ristournes obtenus sur services extérieurs	\N	619000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
478	Personnel intérimaire	\N	621100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
479	Personnel détaché ou prêté à l'entreprise	\N	621400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
480	Commissions et courtages sur achats	\N	622100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
481	Commissions et courtages sur ventes	\N	622200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
482	Rémunérations des transitaires	\N	622400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
483	Rémunérations d'affacturage	\N	622500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
484	Honoraires	\N	622600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
485	Frais d'actes et de contentieux	\N	622700	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
486	Rémunérations d'intermédiaires et honoraires - Divers	\N	622800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
487	Annonces et insertions	\N	623100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
488	Échantillons	\N	623200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
490	Cadeaux à la clientèle	\N	623400	f	16	other	\N	f	Attention déclaration DAS fin d'année	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
491	Primes	\N	623500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
492	Catalogues et imprimés	\N	623600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
493	Publications	\N	623700	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
494	Publicité, publications, relations publiques - Divers (pourboires, dons courants...)	\N	623800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
475	Documentation technique	\N	618300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
489	Foires et expositions	\N	623300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
495	Transports sur achats	\N	624100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
496	Transports sur ventes	\N	624200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
498	Transports administratifs	\N	624400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
499	Transports collectifs du personnel	\N	624700	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
500	Transports divers	\N	624800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
501	Voyages et déplacements	\N	625100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
502	Frais de déménagement	\N	625500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
503	Missions	\N	625600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
504	Réceptions	\N	625700	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
505	Frais postaux et frais de télécommunications	\N	626000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
506	Frais sur titres (achat, vente, garde)	\N	627100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
507	Commissions et frais sur émission d'emprunts	\N	627200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
508	Frais sur effets	\N	627500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
510	Autres frais et commissions sur prestations de services	\N	627800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
511	Concours divers (cotisations...)	\N	628100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
512	Frais de recrutement de personnel	\N	628400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
513	Rabais, remises et ristournes obtenus sur autres services extérieurs	\N	629000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
514	Taxe sur les salaires	\N	631100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
515	Taxe d'apprentissage	\N	631200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
517	Cotisation pour défaut d'investissement obligatoire dans la construction	\N	631400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
518	Autres impôts, taxes et versements assimilés sur rémunérations (administrations des impôts)	\N	631800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
519	Versement de transport	\N	633100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
520	Allocation logement	\N	633200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
522	Participation des employeurs à l'effort de construction	\N	633400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
523	Versements libératoires ouvrant droit à l'exonération de la taxe d'apprentissage	\N	633500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
524	Autres impôts, taxes et versements assimilés sur rémunérations (autres organismes)	\N	633800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
497	Transports entre établissements ou chantiers	\N	624300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
516	Participation des employeurs à la formation professionnelle continue	\N	631300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
521	Participation des employeurs à la formation professionnelle continue	\N	633300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
509	Location de coffres	\N	627600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
525	Cotisation foncière des entreprises	\N	635111	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
526	Cotisation sur la valeur ajoutée des entreprises	\N	635112	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
527	Taxes foncières	\N	635120	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
528	Autres impôts locaux	\N	635130	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
529	Taxe sur les véhicules des sociétés	\N	635140	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
530	Taxes sur le chiffre d'affaires non récupérables	\N	635200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
532	Droits de mutation	\N	635410	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
533	Autres droits	\N	635800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
534	Contribution sociale de solidarité à la charge des sociétés	\N	637100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
535	Taxes perçues par les organismes publics internationaux	\N	637200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
536	Impôts et taxes exigibles à l'étranger	\N	637400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
537	Taxes diverses (autres organismes)	\N	637800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
538	Salaires et appointements	\N	641100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
539	Congés payés	\N	641200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
541	Indemnités et avantages divers	\N	641400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
542	Supplément familial	\N	641500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
543	Rémunération du travail de l'exploitant	\N	644000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
544	Cotisations à l'URSSAF	\N	645100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
545	Cotisations aux mutuelles	\N	645200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
547	Cotisations aux ASSEDIC	\N	645400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
548	Cotisations aux autres organismes sociaux	\N	645800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
549	Cotisations sociales personnelles de l'exploitant	\N	646000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
550	Prestations directes	\N	647100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
551	Versements aux comités d'entreprise et d'établissement	\N	647200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
553	Versements aux autres oeuvres sociales	\N	647400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
554	Médecine du travail, pharmacie	\N	647500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
555	Autres charges de personnel	\N	648000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
556	Crédit d’Impôt Compétitivité Emploi (CICE)	\N	649000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
531	Impôts indirects	\N	635300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
540	Primes et gratifications	\N	641300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
546	Cotisations aux caisses de retraites	\N	645300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
552	Versements aux comités d'hygiène et de sécurité	\N	647300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
557	Redevances pour concessions brevets, licences, marques, procédés, logiciels	\N	651100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
558	Droits d'auteur et de reproduction	\N	651600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
559	Autres droits et valeurs similaires	\N	651800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
561	Créances de l'exercice	\N	654100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
562	Créances des exercices antérieurs	\N	654400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
563	Quote-part de bénéfice transférée (comptabilité du gérant)	\N	655100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
564	Quote-part de perte supportée (comptabilité des associés non gérants)	\N	655500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
565	Charges diverses de gestion courante	\N	658000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
566	Intérêts des emprunts et dettes assimilées	\N	661160	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
567	Intérêts des dettes rattachées à des participations	\N	661170	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
568	Intérêts des comptes courants et des dépôts créditeurs	\N	661500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
569	Intérêts bancaires et sur opérations de financement (escompte, ...)	\N	661600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
570	Intérêts des obligations cautionnées	\N	661700	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
571	Intérêts des dettes commerciales	\N	661810	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
572	Intérêts des dettes diverses	\N	661880	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
573	Pertes sur créances liées à des participations	\N	664000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
574	Escomptes accordés	\N	665000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
575	Pertes de change	\N	666000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
576	Charges nettes sur cessions de valeurs mobilières de placement	\N	667000	f	16	other	\N	f	(contrepartie 767)	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
577	Autres charges financières	\N	668000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
578	Charges exceptionnelles - Pénalités sur marchés (et dédits payés sur achats et ventes)	\N	671100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
579	Charges exceptionnelles - Pénalités, amendes fiscales et pénales	\N	671200	f	16	other	\N	f	PV code de la route non déductibles	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
581	Charges exceptionnelles - Créances devenues irrécouvrables dans l'exercice	\N	671400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
582	Charges exceptionnelles - Subventions accordées	\N	671500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
583	Charges exceptionnelles - Rappels d'impôts (autres qu'impôts sur les bénéfices)	\N	671700	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
584	Autres charges exceptionnelles sur opération de gestion	\N	671800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
560	Jetons de présence	\N	653000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
580	Charges exceptionnelles - Dons, libéralités	\N	671300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
585	Charges exceptionnelles sur exercices antérieurs (en cours d'exercice seulement)	\N	672000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
586	Valeurs comptables des éléments d'actif cédés - Immobilisations incorporelles	\N	675100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
587	Valeurs comptables des éléments d'actif cédés - Immobilisations corporelles	\N	675200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
588	Valeurs comptables des éléments d'actif cédés - Immobilisations financières	\N	675600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
589	Valeurs comptables des éléments d'actif cédés - Autres éléments d'actif	\N	675800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
590	Charges exceptionnelles - Malis provenant de clauses d'indexation	\N	678100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
591	Charges exceptionnelles - Lots	\N	678200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
593	Charges exceptionnelles diverses	\N	678800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
594	Dotations aux amortissements sur immobilisations incorporelles	\N	681110	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
595	Dotations aux amortissements sur immobilisations corporelles	\N	681120	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
596	Dotations aux amortissements des charges d'exploitation à répartir	\N	681200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
597	Dotations aux provisions pour risques et charges d'exploitation	\N	681500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
598	Dotations aux dépréciations des immobilisations incorporelles	\N	681610	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
599	Dotations aux dépréciations des immobilisations corporelles	\N	681620	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
600	Dotations aux dépréciations des stocks et en-cours	\N	681730	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
601	Dotations aux dépréciations des créances	\N	681740	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
602	Dotations aux amortissements des primes de remboursement des obligations	\N	686100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
603	Dotations aux provisions pour risques et charges financiers	\N	686500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
604	Dotations aux dépréciations des immobilisations financières	\N	686620	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
605	Dotations aux dépréciations des valeurs mobilières de placement	\N	686650	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
606	Autres dotations aux amortissements, dépréciations et provisions - Charges financières	\N	686800	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
607	Dotations aux amortissements exceptionnels des immobilisations	\N	687100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
608	Dotations aux provisions réglementées exceptionnelles (immobilisations) - Amortissements dérogatoires	\N	687250	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
592	Charges exceptionnelles - Malis provenant du rachat par l'entreprise d'actions et obligations émises par elle-même	\N	678300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
609	Dotations aux provisions réglementées exceptionnelles (stocks)	\N	687300	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
610	Dotations aux autres provisions réglementées exceptionnelles	\N	687400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
611	Dotations aux provisions exceptionnelles	\N	687500	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
614	Impôts sur les bénéfices dus en France	\N	695100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
615	Contribution additionnelle à l'impôt sur les bénéfices	\N	695200	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
616	Impôts sur les bénéfices dus à l'étranger	\N	695400	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
617	Supplément d'impôt sur les sociétés lié aux distributions	\N	696000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
618	Imposition forfaitaire annuelle des sociétés	\N	697000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
619	Intégration fiscale - Charges	\N	698100	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
620	Intégration fiscale - Produits	\N	698900	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
621	Produits, Reports en arrière des déficits	\N	699000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
622	Ventes de produits finis (ou groupe) A	\N	701100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
623	Ventes de produits finis (ou groupe) B	\N	701200	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
624	Ventes de produits intermédiaires	\N	702000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
626	Ventes de travaux de catégorie (ou activité) A	\N	704100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
627	Ventes de travaux de catégorie (ou activité) B	\N	704200	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
628	Ventes d'études	\N	705000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
629	Ventes de prestations de services	\N	706000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
630	Ventes de marchandises (ou groupe) A	\N	707100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
631	Ventes de marchandises (ou groupe) B	\N	707200	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
633	Produits des services exploités dans l'intérêt du personnel	\N	708100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
634	Commissions et courtages	\N	708200	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
636	Mise à disposition de personnel facturée	\N	708400	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
637	Ports et frais accessoires facturés	\N	708500	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
638	Bonis sur reprises d'emballages consignés	\N	708600	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
639	Bonifications obtenues des clients et primes sur ventes	\N	708700	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
613	Participation des salariés aux résultats	\N	691000	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
625	Ventes de produits résiduels	\N	703000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
632	Ventes de marchandises à l'exportation	\N	707300	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
635	Locations diverses	\N	708300	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
612	Dotations aux dépréciations exceptionnelles	\N	687600	f	16	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
640	Autres produits d'activités annexes (cessions d'approvisionnements...)	\N	708800	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
641	Rabais, remises et ristournes sur ventes de produits finis	\N	709100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
642	Rabais, remises et ristournes sur ventes de produits intermédiaires	\N	709200	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
643	Rabais, remises et ristournes sur travaux	\N	709400	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
644	Rabais, remises et ristournes sur études	\N	709500	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
645	Rabais, remises et ristournes sur prestations de services	\N	709600	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
646	Rabais, remises et ristournes sur ventes de marchandises	\N	709700	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
647	Rabais, remises et ristournes sur produits des activités annexes	\N	709800	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
648	Variation des en-cours de production de biens - Produits en cours	\N	713310	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
649	Variation des en-cours de production de biens - Travaux en cours	\N	713350	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
650	Variation des en-cours de production de services - Études en cours	\N	713410	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
651	Variation des en-cours de production de services - Prestations de services en cours	\N	713450	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
652	Variation des stocks de produits intermédiaires	\N	713510	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
653	Variation des stocks de produits finis	\N	713550	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
654	Variation des stocks de produits résiduels	\N	713580	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
656	Production immobilisée - Immobilisations corporelles	\N	722000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
657	Subventions d'exploitation	\N	740000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
658	Redevances pour concessions, brevets, licences, marques, procédés, logiciels	\N	751100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
659	Droits d'auteur et de reproduction	\N	751600	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
660	Redevances pour autres droits et valeurs similaires	\N	751800	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
661	Revenus des immeubles non affectés aux activités professionnelles	\N	752000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
663	Ristournes perçues des coopératives (provenant des excédents)	\N	754000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
664	Quote-part de perte transférée (comptabilité du gérant)	\N	755100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
665	Quote-part de bénéfice attribuée (comptabilité des associés non-gérants)	\N	755500	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
666	Produits divers de gestion courante	\N	758000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
655	Production immobilisée - Immobilisations incorporelles	\N	721000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
662	Jetons de présence et rémunérations d'administrateurs, gérants..	\N	753000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
667	Revenus des titres de participation	\N	761100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
668	Revenus sur autres formes de participation	\N	761600	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
669	Revenus des titres immobilisés	\N	762100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
670	Revenus des prêts	\N	762600	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
671	Revenus des créances immobilisées	\N	762700	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
672	Revenus des créances commerciales	\N	763100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
673	Revenus des créances diverses	\N	763800	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
674	Revenus des valeurs mobilières de placement	\N	764000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
675	Escomptes obtenus	\N	765000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
676	Gains de change	\N	766000	f	13	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
677	Produits nets sur cessions de valeurs mobilières de placement	\N	767000	f	14	other	\N	f	(contrepartie 667)	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
678	Autres produits financiers	\N	768000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
679	Produits exceptionnels - Dédits et pénalités perçus sur achats et sur ventes	\N	771100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
681	Produits exceptionnels - Rentrées sur créances amorties	\N	771400	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
682	Produits exceptionnels - Subventions d'équilibre	\N	771500	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
683	Produits exceptionnels - Dégrèvements d'impôts autres qu'impôts sur les bénéfices	\N	771700	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
684	Autres produits exceptionnels sur opérations de gestion	\N	771800	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
685	Produits exceptionnels sur exercices antérieurs (en cours d'exercice seulement)	\N	772000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
686	Produits exceptionnels des cessions d'éléments d'actif - Immobilisations incorporelles	\N	775100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
687	Produits exceptionnels des cessions d'éléments d'actif - Immobilisations corporelles	\N	775200	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
688	Produits exceptionnels des cessions d'éléments d'actif - Immobilisations financières	\N	775600	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
689	Produits exceptionnels des cessions d'éléments d'actif - Autres éléments d'actif	\N	775800	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
690	Produits exceptionnels - Quote-part des subventions d'investissement virée au résultat de l'exercice	\N	777000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
691	Produits exceptionnels - Bonis provenant de clauses d'indexation	\N	778100	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
692	Produits exceptionnels - Lots	\N	778200	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
680	Produits exceptionnels - Libéralités reçues	\N	771300	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
693	Produits exceptionnels - Bonis provenant du rachat par l'entreprise d'actions et d'obligations émises par elle-même	\N	778300	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
694	Produits exceptionnels divers	\N	778800	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
695	Reprises sur amortissements des immobilisations incorporelles	\N	781110	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
696	Reprises sur amortissements des immobilisations corporelles	\N	781120	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
697	Reprises sur provisions d'exploitation	\N	781500	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
698	Reprises sur dépréciations des immobilisations incorporelles	\N	781610	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
699	Reprises sur dépréciations des immobilisations corporelles	\N	781620	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
700	Reprises sur dépréciations des actifs circulants - Stocks et en-cours	\N	781730	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
701	Reprises sur dépréciations des actifs circulants - Créances	\N	781740	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
702	Reprises sur provisions financières	\N	786500	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
703	Reprises sur dépréciations des immobilisations financières	\N	786620	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
704	Reprises sur dépréciations des valeurs mobilières de placement	\N	786650	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
705	Reprises sur provisions réglementées (immobilisations) - Amortissements dérogatoires	\N	787250	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
706	Reprises sur provisions réglementées (immobilisations) - Provision spéciale de réévaluation	\N	787260	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
707	Reprises sur provisions réglementées (immobilisations) - Plus-values réinvesties	\N	787270	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
709	Reprises sur autres provisions réglementées	\N	787400	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
710	Reprises sur provisions exceptionnelles	\N	787500	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
713	Transferts de charges financières	\N	796000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
714	Transferts de charges exceptionnelles	\N	797000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
716	Banque	\N	512001	f	3	liquidity	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
717	Profits/pertes non distribués	\N	999999	f	12	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:06:53.166234
712	Transferts de charges d'exploitation	\N	791000	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
708	Reprises sur provisions réglementées (stocks)	\N	787300	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
715	Espèces	\N	530001	f	3	liquidity	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
711	Reprises sur dépréciations exceptionnelles	\N	787600	f	14	other	\N	f	\N	1	\N	1	2019-02-14 15:06:53.166234	1	2019-02-14 15:09:48.936423
\.
