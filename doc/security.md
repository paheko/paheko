# Principes de sécurité

## Adresses URL

* toutes les URL doivent être vérifiée avec `Utils::isValidURL()`
* toutes les URL doivent qui donnent lieu à une requête HTTP depuis PHP doivent être validées via `Utils::validateExternalURL` (empêche l'accès aux adresses du réseau privé)
