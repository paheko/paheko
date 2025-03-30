-- In previous version of reçus fiscaux, the transaction ID
-- was saved in id_year instead of the real year ID, let's try to fix this
CREATE TEMP TABLE recus_years (id, id_year);

INSERT INTO recus_years
	SELECT r.id, t.id_year
	FROM module_data_recus_fiscaux r
	LEFT JOIN acc_years y ON y.id = json_extract(r.document, '$.id_year')
	LEFT JOIN acc_transactions t ON t.id = json_extract(r.document, '$.id_year')
	WHERE
		json_extract(r.document, '$.id_year') IS NOT NULL
		AND y.id IS NULL;

UPDATE module_data_recus_fiscaux AS m
	SET document = json_patch(document, json_object('id_year', (SELECT id_year FROM recus_years WHERE id = m.id)))
	WHERE EXISTS(SELECT id FROM recus_years WHERE id = m.id);

DROP TABLE recus_years;
