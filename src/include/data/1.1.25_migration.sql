UPDATE plugins_signaux SET signal = 'home.banner' WHERE signal = 'accueil.banniere';
UPDATE plugins_signaux SET signal = 'reminder.send.after' WHERE signal = 'rappels.auto';
UPDATE plugins_signaux SET signal = 'email.send.before' WHERE signal = 'email.envoi';
