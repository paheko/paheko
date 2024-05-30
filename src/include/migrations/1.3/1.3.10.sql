UPDATE config_users_fields
	SET type = 'textarea', system = system | (0x01 << 6)
	WHERE name = 'adresse' AND (system & (0x01 << 5));