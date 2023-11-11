UPDATE config_users_fields SET default_value = 'NOW()' WHERE default_value = '''=NOW''' OR default_value = '=NOW';
