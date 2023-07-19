CREATE TABLE IF NOT EXISTS module_data_bookings (
	id INTEGER NOT NULL PRIMARY KEY,
	key TEXT NULL,
	document TEXT NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS module_data_bookings_key ON module_data_bookings (key);

INSERT INTO module_data_bookings SELECT
	NULL, uuid(),
	json_object(
		'type', 'event',
		'label', nom,
		'description', description,
		'use_openings', json('false'),
		'openings_seats', NULL,
		'openings_slots', NULL,
		'openings_delay', NULL,
		'use_closings', json('false'),
		'fields', json_array(),
		'email', NULL,
		'archived', json('false')
	)
	FROM plugin_reservations_categories;

INSERT INTO module_data_bookings SELECT
	NULL, uuid(),
	json_object(
		'type', 'booking',
		'event', (SELECT key FROM module_data_bookings
			WHERE json_extract(document, '$.label') = (SELECT nom FROM plugin_reservations_categories
				WHERE id = (SELECT categorie FROM plugin_reservations_creneaux WHERE id = plugin_reservations_personnes.creneau))),
		'slot', NULL,
		'date', date,
		'name', nom,
		'email', NULL,
		'id_user', NULL,
		'fields', NULL
	)
	FROM plugin_reservations_personnes;

DROP TABLE plugin_reservations_categories;
DROP TABLE plugin_reservations_creneaux;
DROP TABLE plugin_reservations_personnes;
