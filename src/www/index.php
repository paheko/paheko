<?php

namespace Garradin;

use Garradin\Users\Emails;
use Garradin\Web\Web;

require __DIR__ . '/_inc.php';

// Handle __un__subscribe URL
if (!empty($_GET['un'])) {
	$params = array_intersect_key($_GET, ['un' => null, 'v' => null]);

	// RFC 8058
	if (!empty($_POST['Unsubscribe']) && $_POST['Unsubscribe'] == 'Yes') {
		$email = Emails::getEmailFromOptout($params['un']);

		if (!$email) {
			throw new UserException('Adresse email introuvable.');
		}

		$email->setOptout();
		$email->save();
		http_response_code(200);
		echo 'Unsubscribe successful';
		exit;
	}

	Utils::redirect('!optout.php?' . http_build_query($params));
}

Web::dispatchURI();
