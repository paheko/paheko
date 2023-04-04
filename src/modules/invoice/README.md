# Documentation du module “Devis et factures”.

## Processus

Création d'un devis
    ↳ Directement signé ↴
    ↳ Brouillon → Signé (par l'asso) → En attente de validation (client) → Validé → Archivé
                                              ↳ Refusé (client)               ↳ Génération d'une facture → En attente de paiement → Payée → Archivée
                                                    ↳ Archivé                                                      ↳ Annulée → Archivée

## Annulation (croix ✘)

Il est possible d'annuler n'importe quel(le) devis ou facture s'il ne s'agit pas d'un brouillon (les brouillons peuvent être supprimés).
Toute annulation est définitive !

## Archivage (cadena 🔒)

Il est possible d'archiver n'importe quel devis s'il ne s'agit pas d'un brouillon.
Il est possible d'archiver une facture si elle est annulée ou si elle n'est pas en attente de paiement.
L'archivage n'est pas définitif, un document peut-être sorti des archives (bouton cadena ouvert 🔓).

## Modification d'un·e devis/facture

### Brouillons

Seuls les brouillons de devis peuvent être modifiés. Une fois un devis signé, il ne peut plus être modifié.
Seuls les brouillons peuvent être supprimés.

### Factures

Les factures ne sont pas modifiables.
Si vous commettez une erreur sur une factures (la marquer comme payée par erreur), vous avez deux possibilités :
+ annuler la facture, la dupliquer et recommencer avec la nouvelle facture.
+ annuler la facture, retourner sur le devis et générer une nouvelle facture.
Dans les deux cas la facture erronée ne sera pas supprimable, mais vous pourrez l'archiver.

## Membre associé·e à un·e devis/facture

À la création d'un devis vous pouvez associer un·e membre comme destinataire pour retrouver ensuite d'un clic tous les documents associés.
Même si vous sélectionnez un·e membre, il est possible de saisir manuellement la raison sociale (et adresse) qui alors prévaudra sur le document.

## Intitulé

Les factures possèdent le même intitulé que le devis originel. Il n'est pour le moment pas possible de le modifier sur la facture.

## Remarques internes

Les remarques internes ne sont pas affichées sur les documents PDF.
Vous pouvez éditer ces remarques peu importe le statut du document (payé, annulé, archivé...).
