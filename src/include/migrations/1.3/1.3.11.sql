UPDATE config_users_fields
	SET system = system | (0x01 << 7)
	WHERE name = 'code_postal' AND (system & (0x01 << 5));

UPDATE config_users_fields
	SET system = system | (0x01 << 8)
	WHERE name = 'ville' AND (system & (0x01 << 5));
