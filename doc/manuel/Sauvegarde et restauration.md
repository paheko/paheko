# Sauvegarde et restauration



## Messages d'erreur

### Le fichier fourni est corrompu. Certaines clés étrangères référencent des lignes qui n'existent pas.

Ce message indiquent que certaines lignes dans une table font référence à des lignes d'une autre table qui n'existent pas.

Cette erreur se produit lorsque des modifications manuelles ont été apportées à une base de données.

Pour trouver les lignes qui sont invalides, utiliser un outil de gestion de base de données SQLite et lancer la commande suivante :

	PRAGMA schema.integrity_check;

