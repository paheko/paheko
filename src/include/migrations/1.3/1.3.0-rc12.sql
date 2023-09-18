UPDATE config_users_fields SET required = 0 WHERE system & (0x01 << 1);
