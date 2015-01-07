CREATE TABLE plugins_skel_boucles
(
    plugin TEXT NOT NULL REFERENCES plugins (id),
    nom TEXT NOT NULL
);