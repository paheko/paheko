<?php

require __DIR__ . '/include/class.compta_comptes.php';
require __DIR__ . '/include/lib.utils.php';

$plan = <<<EOF_PLAN
Classe 1 — Comptes de capitaux (Fonds propres, emprunts et dettes assimilés)

10 FONDS ASSOCIATIFS ET RÉSERVES

    102 Fonds associatif sans droit de reprise

        1021 Valeur du patrimoine intégré
        1022 Fonds statutaire
        1024 Apports sans droit de reprise

    103 Fonds associatif avec droit de reprise

        1034 Apports avec droit de reprise

    105 Écarts de réévaluation

    106 Réserves

        1063 Réserves statutaires ou contractuelles
        1064 Réserves réglementées
        1068 Autres réserves (dont réserves pour projet associatif)

11 REPORT À NOUVEAU

    110 Report à nouveau (Solde créditeur)
    119 Report à nouveau (Solde débiteur)

12 RÉSULTAT NET DE L'EXERCICE

    120 Résultat de l'exercice (excédent)
    129 Résultat de l'exercice (déficit)

13 SUBVENTIONS D'INVESTISSEMENT AFFECTÉES A DES BIENS NON RENOUVELABLES

    131 Subventions d'investissement (renouvelables)
    139 Subventions d'investissement inscrites au compte de résultat

14 PROVISIONS REGLEMENTÉES

15 PROVISIONS

    151 Provisions pour risques

    157 Provisions pour charges à répartir sur plusieurs exercices

    158 Autres provisions pour charges


16 EMPRUNTS ET DETTES ASSIMILÉES

    164 Emprunts auprès des établissements de crédits

    165 Dépôts et cautionnements reçus

    167 Emprunts et dettes assorties de conditions particulières

    168 Autres emprunts et dettes assimilés

17 DETTES RATTACHÉES À DES PARTICIPATIONS

18 COMPTES DE LIAISON DES ÉTABLISSEMENTS

    181 Apports permanents entre siège social et établissements
    185 Biens et prestations de services échangés entre établissements et siège social
    186 Biens et prestations de services échangés entre établissements (charges)
    187 Biens et prestations de services échangés entre établissements (produits)

19 FONDS DÉDIÉS

    194 Fonds dédiés sur subventions de fonctionnement
    195 Fonds dédiés sur dons manuels affectés
    197 Fonds dédiés sur legs et donations affectés
    198 Excédent disponible après affectation au projet associatif
    199 Reprise des fonds affectés au projet associatif

Classe 2 — Comptes d'immobilisations

20 IMMOBILISATIONS INCORPORELLES

    200 Immobilisations incorporelles

21 IMMOBILISATIONS CORPORELLES

    210 Investissements

22 IMMOBILISATIONS GREVÉES DE DROITS

    228 Immobilisations grevées de droits
    229 Droits des propriétaires

26 PARTICIPATIONS ET CRÉANCES RATTACHÉES A DES PARTICIPATIONS

    261 Titres de participation

27 AUTRES IMMOBILISATIONS FINANCIÈRES

    270 Participations financières
    275 Dépôts et cautionnements versés

28 AMORTISSEMENTS DES IMMOBILISATIONS

    280 Amortissements des immobilisations incorporelles
    281 Amortissements des immobilisations corporelles

29 DÉPRÉCIATION DES IMMOBILISATIONS

    290 Dépréciation des immobilisations incorporelles
    291 Dépréciation des immobilisations corporelles

Classe 3 — Comptes de stocks

31 MATIERES PREMIERES ET FOURNITURES

    311 Matières
    317 Fournitures

32 AUTRES APPROVISIONNEMENTS

    321 Matières consommables
    322 Fournitures consommables

33 EN-COURS DE PRODUCTION DE BIENS

    331 Produits en cours
    335 Travaux en cours

34 EN-COURS DE PRODUCTION DE SERVICES

35 STOCKS DE PRODUITS

    351 Produits intermédiaires
    355 Produits finis
    358 Produits résiduels

        3581 Déchets
        3585 Rebuts
        3586 Matière de récupération

37 STOCKS DE MARCHANDISES

    370 Autres stocks de marchandises

39 PROVISIONS POUR DEPRECIATION DES STOCKS ET EN-COURS

    391 Provisions pour dépréciation des matières premières et fournitures

Classe 4 — Comptes de tiers

40 FOURNISSEURS ET COMPTES RATTACHÉS

    401 Fournisseurs

        4010 Autres fournisseurs

    408 Fournisseurs - Factures non parvenues

    409 Avances aux fournisseurs

41 USAGERS ET COMPTES RATTACHÉS

    411 Usagers

        4110 Autres usagers

    419 Avances aux usagers

42 PERSONNEL ET COMPTES RATTACHÉS

    421 Personnel - Rémunérations dues
        4210 Autres membres du personnel
    425 Personnel - Avances et acomptes
    428 Personnel - Charges à payer et produits à recevoir

43 SÉCURITÉ SOCIALE ET AUTRES ORGANISMES SOCIAUX

    430 Dettes et crédits envers les organismes sociaux
    431 Sécurité sociale
    437 Autres organismes sociaux

        4372 Mutuelles
        4373 Caisse de retraite et de prévoyance
        4374 Caisse d'allocations de chômage - Pôle emploi
        4375 AGESSA
        4378 Autres organismes sociaux - Divers

    438 Organismes sociaux - Charges à payer et produits à recevoir

        4382 Charges sociales sur congés à payer
        4386 Autres charges à payer
        4387 Produits à recevoir

    439 Avances auprès des organismes sociaux

44 ÉTAT ET AUTRES COLLECTIVITÉS PUBLIQUES

    441 État - Subventions à recevoir

        4411 Subventions d'investissement
        4417 Subventions d'exploitation
        4418 Subventions d'équilibre
        4419 Avances sur subventions

    442 État - Impôts et taxes recouvrables sur des tiers
    444 État - Impôts sur les bénéfices
    445 État - Taxes sur le chiffre d'affaires

        4455 Taxes sur le chiffre d'affaires à décaisser

            44551 TVA à décaisser
            44558 Taxes assimilées à la TVA

        4456 Taxes sur le chiffre d'affaires déductibles

            44562 TVA sur immobilisations
            44566 TVA sur autres biens et services

        4457 Taxes sur le chiffre d'affaires collectées par l'association
        4458 Taxes sur le chiffre d'affaires à régulariser ou en attente

            44581 Acomptes - Régime simplifié d'imposition
            44582 Acomptes - Régime du forfait
            44583 Remboursement de taxes sur le chiffre d'affaires demandé
            44584 TVA récupérée d'avance
            44586 Taxes sur le chiffre d'affaires sur factures non parvenues
            44587 Taxes sur le chiffre d'affaires sur factures à établir

    447 Autres impôts, taxes et versements assimilés

        4471 Autres impôts, taxes et versements assimilés sur rémunérations (Administration des impôts)

            44711 Taxe sur les salaires
            44713 Participation des employeurs à la formation professionnelle continue
            44714 Cotisation par défaut d'investissement obligatoire dans la construction
            44718 Autres impôts, taxes et versements assimilés

        4473 Autres impôts, taxes et versements assimilés sur rémunérations (Autres organismes)

            44733 Participation des employeurs à la formation professionnelle continue
            44734 Participation des employeurs à l'effort de construction (versements à fonds perdus)

        4475 Autres impôts, taxes et versements assimilés (Administration des impôts)
        4477 Autres impôts, taxes et versements assimilés (Autres organismes)

    448 État - Charges à payer et produits à recevoir

        4482 Charges fiscales sur congés à payer
        4486 Autres charges à payer
        4487 Produits à recevoir

    449 Avances auprès de l'état et des collectivités publiques

45 CONFÉDÉRATION, FÉDÉRATION, UNIONS ET ASSOCIATIONS AFFILIÉES

    451 Confédération, fédération et associations affiliées - Compte courant
    455 Sociétaires - Comptes courants

46 DÉBITEURS DIVERS ET CRÉDITEURS DIVERS

    467 Autres comptes débiteurs et créditeurs
    468 Divers - Charges à payer et produits à recevoir

        4686 Charges à payer
        4687 Produits à recevoir

47 COMPTES TRANSITOIRES OU D'ATTENTE

    471 Recettes à classer
    472 Dépenses à classer et à régulariser

48 COMPTES DE RÉGULARISATION

    481 Charges à répartir sur plusieurs exercices
    486 Charges constatées d'avance
    487 Produits constatés d'avance

49 DEPRECIATION DES COMPTES DE TIERS

    491 Dépréciation des comptes clients
    496 Dépréciation des comptes débiteurs divers

Classe 5 — Comptes financiers

51 BANQUES, ÉTABLISSEMENTS FINANCIERS ET ASSIMILÉS

512 Banques

53 CAISSE

    530 Caisse

58 VIREMENTS INTERNES

Classe 6 — Comptes de charges

60 ACHATS

    601 Achats stockés - Matières premières et fournitures

    602 Achats stockés - Autres approvisionnements

    604 Achat d'études et prestations de services

    606 Achats non stockés de matières et fournitures

        6061 Fournitures non stockables (eau, énergie...)

        6063 Fournitures d'entretien et de petit équipement
        6064 Fournitures administratives
        6068 Autres matières et fournitures

    607 Achats de marchandises

61 SERVICES EXTÉRIEURS

    611 Sous-traitance générale
    612 Redevances de crédit-bail

    613 Locations

    614 Charges locatives et de co-propriété
    615 Entretiens et réparations

    616 Primes d'assurance

    618 Divers

62 AUTRES SERVICES EXTÉRIEURS

    621 Personnel extérieur à l'association

    622 Rémunérations d'intermédiaires et honoraires

        6226 Honoraires
        6227 Frais d'actes et de contentieux
        6228 Divers

    623 Publicité, publications, relations publiques

    624 Transports de biens et transports collectifs du personnel

    625 Déplacements, missions et réceptions

    626 Frais postaux et de télécommunications

    627 Services bancaires et assimilés

    628 Divers

63 IMPÔTS, TAXES ET VERSEMENTS ASSIMILÉS

    631 Impôts, taxes et versements assimilés sur rémunérations (Administration des impôts)

        6311 Taxes sur les salaires
        6313 Participations des employeurs à la formation professionnelle continue

    635 Autres impôts, taxes et versements assimilés (Administration des impôts)

        6351 Impôts directs (sauf impôts sur les bénéfices)
        6353 Impôts indirects

    637 Autres impôts, taxes et versements assimilés (Autres organismes)

64 CHARGES DE PERSONNEL

    641 Rémunérations du personnel

    643 Rémunérations du personnel artistique et assimilés

    645 Charges de sécurité sociale et de prévoyance

    647 Autres charges sociales

    648 Autres charges de personnel

65 AUTRES CHARGES DE GESTION COURANTE

    658 Charges diverses de gestion courante

66 CHARGES FINANCIÈRES

    661 Charges d'intérêts

67 CHARGES EXCEPTIONNELLES

    671 Charges exceptionnelles sur opérations de gestion

        6713 Dons, libéralités

    678 Autres charges exceptionnelles

        6788 Charges exceptionnelles diverses

68 DOTATIONS AUX AMORTISSEMENTS, DÉPRÉCIATIONS, PROVISIONS ET ENGAGEMENTS

    681 Dotations aux amortissements, dépréciations et provisions - Charges d'exploitation

        6811 Dotations aux amortissements des immobilisations incorporelles et corporelles

            68111 Immobilisations incorporelles
            68112 Immobilisations corporelles

    686 Dotations aux amortissements, dépréciations et provisions - Charges financières

69 PARTICIPATION DES SALARIÉS - IMPÔTS SUR LES BÉNÉFICES ET ASSIMILÉS

    695 Impôts sur les sociétés (y compris impôts sur les sociétés des personnes morales non lucratives)

Classe 7 — Comptes de produits

70 VENTES DE PRODUITS FINIS, PRESTATIONS DE SERVICES, MARCHANDISES

    701 Ventes de produits finis
    706 Prestations de services
    707 Ventes de marchandises
    708 Produits des activités annexes

74 SUBVENTIONS D'EXPLOITATION

    740 Subventions reçues

75 AUTRES PRODUITS DE GESTION COURANTE

    754 Collectes
    756 Cotisations
    758 Produits divers de gestion courante

        7587 Ventes de dons en nature
        7588 Autres produits de la générosité du public

76 PRODUITS FINANCIERS

    760 Produits financiers

77 PRODUITS EXCEPTIONNELS

    771 Produits exceptionnels sur opérations de gestion

        7713 Libéralités reçues
        7715 Subventions d'équilibre

    775 Produits des cessions d'éléments d'actifs

    778 Autres produits exceptionnels

        7780 Manifestations diverses
        7788 Produits exceptionnels divers

79 TRANSFERT DE CHARGES

    791 Transferts de charges d'exploitation
    796 Transferts de charges financières
    797 Transferts de charges exceptionnels

Classe 8 ­— Contributions bénévoles en nature

86 RÉPARTITION PAR NATURE DE CHARGES

    861 Mise à dispositions gratuites de biens
    862 Prestations
    864 Personnel bénévole

86 RÉPARTITION PAR NATURE DE RESSOURCES

    870 Bénévolat
    871 Prestations en nature
    875 Dons en nature

Classe 9 — Comptes analytiques

EOF_PLAN;

$src = explode("\n", $plan);
$plan = array();

foreach ($src as $line)
{
    $line = trim($line);
    if (preg_match('!^(\d+)\s+(.+)$!', $line, $match))
    {
        $code = (int)$match[1];
        $nom = trim($match[2]);
        $parent = (int)substr($match[1], 0, -1);
    }
    elseif (preg_match('!^Classe (\d+)\s+.*$!', $line, $match))
    {
        $code = (int)$match[1];
        $nom = trim($match[0]);
        $parent = 0;
    }
    elseif ($line)
    {
        echo "$line\n";
    }

    $classe = substr((string)$code, 0, 1);

    if ($classe == 1)
    {
        if ($code == 11 || $code == 12)
        {
            $position = Garradin_Compta_Comptes::PASSIF | Garradin_Compta_Comptes::ACTIF;
        }
        elseif ($code == 119 || $code == 129 || $code == 139)
        {
            $position = Garradin_Compta_Comptes::ACTIF;
        }
        else
        {
            $position = Garradin_Compta_Comptes::PASSIF;
        }
    }
    elseif ($classe == 2 || $classe == 3 || $classe == 5)
    {
        $position = Garradin_Compta_Comptes::ACTIF;
    }
    // Comptes de classe 4, c'est compliqué là
    elseif ($classe == 4)
    {
        if (strlen($code) > 2)
        {
            $prefixe = substr($code, 0, 3);

            if ($prefixe == 401 || $prefixe == 411 || $prefixe == 421 || $prefixe == 430 || $prefixe == 440
                || $prefixe == 468 || $code == 4686 || $prefixe == 472 || $prefixe == 487)
            {
                $position = Garradin_Compta_Comptes::PASSIF;
            }
            else
            {
                $position = Garradin_Compta_Comptes::ACTIF;
            }
        }
        else
        {
            $position = Garradin_Compta_Comptes::PASSIF | Garradin_Compta_Comptes::ACTIF;
        }
    }
    elseif ($classe == 6)
    {
        $position = Garradin_Compta_Comptes::CHARGE;
    }
    elseif ($classe == 7)
    {
        $position = Garradin_Compta_Comptes::PRODUIT;
    }
    elseif ($classe == 8)
    {
        if (substr($code, 0, 2) == 86)
        {
            $position = Garradin_Compta_Comptes::CHARGE;
        }
        elseif (substr($code, 0, 2) == 87)
        {
            $position = Garradin_Compta_Comptes::PRODUIT;
        }
    }
    elseif ($classe == 9)
    {
        $position = Garradin_Compta_Comptes::CHARGE | Garradin_Compta_Comptes::PRODUIT;
    }

    $plan[$code] = array(
        'code'      =>  $code,
        'nom'       =>  $nom,
        'parent'    =>  $parent,
        'position'  =>  $position,
    );
}

$json = utils::json_readable_encode($plan, 0);
file_put_contents('include/plan_comptable.json', $json);

die("OK\n");

?>