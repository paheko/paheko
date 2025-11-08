<?php

use KD2\Mail\Mailbox;

$cfg = $_SERVER['argv'][1] ?? null;

if (!$cfg || !is_readable($cfg)) {
	printf("Usage: %s CONFIG_FILE\n", $_SERVER['argv'][0]);
	exit(1);
}

require __DIR__ . '/../src/include/lib/KD2/Mail/Mailbox.php';

TestMailbox::all($cfg);

class TestMailbox
{
	// Orange/SFR/Neuf = QUARANTINE, Yahoo.fr = Bulk Mail
	const JUNK_FOLDER_NAMES = ['QUARANTINE', 'Bulk Mail', 'Junk'];

	const PROMO_FLAGS = [
		// SFR = les messages identifiés comme "NEWSLETTER" sont mis dans le dossier
		// "Infos et promos"
		'NEWSLETTER',
	];

	const MESSAGE_ID_PREFIX = 'pahekourriel.';

	protected stdClass $config;
	protected Mailbox $mailbox;

	static public function all(string $file): stdClass
	{
		$ini = parse_ini_file($file, true);

		$defaults = [
			'user'              => null,
			'provider'          => null,
			'address'           => null,
			'mark_as_important' => true,
			'move_if_spam'      => true,
			'others_are_spam'   => false,
		];

		foreach ($ini as $address => $config) {
			$config = (object) array_merge($defaults, $config);
			$config->address = $address;
			$config->user ??= $address;
			$config->provider ??= substr($address, strrpos($address, '@')+1);

			$t = new self($config);
			var_dump($t->report());
		}
	}

	public function __construct(stdClass $config)
	{
		$this->config = $config;
	}

	public function report(): array
	{
		$m = $this->mailbox = new Mailbox($this->config->imap);
		//$m->setLogFilePointer(STDOUT);
		$m->setLogin($this->config->user, $this->config->password);
		$this->config->password = null;
		$folders = $m->listFolders();
		$report = [];

		foreach ($folders as $key => $folder) {
			if (in_array('Junk', $folder->flags)
				|| $key === 'INBOX'
				|| in_array($key, self::JUNK_FOLDER_NAMES)) {
				$this->exploreFolder($key, $report);
			}
			else {
				// Not interested
				continue;
			}
		}

		return $report;
	}

	protected function exploreFolder(string $folder, array &$report)
	{
		$date = new \DateTime('30 days ago');

		foreach ($this->mailbox->iterateMessages($folder, ['since' => $date]) as $msg) {
			// Not one of our messages
			if (0 !== strpos($msg->message_id, self::MESSAGE_ID_PREFIX)) {
				continue;
			}

			$id = substr($msg->message_id, strlen(self::MESSAGE_ID_PREFIX));
			$id = (int) substr($id, 0, strpos($id, '.')-1);

			if (!$id) {
				continue;
			}

			$error = null;

			// Identify messages marked as promo (SFR)
			if ($folder === 'INBOX'
				&& in_array(self::PROMO_FLAGS, $msg->flags)) {
				$error = 'promotional';
			}
			elseif ($folder !== 'INBOX') {
				$error = 'junk';
			}
			else {
				continue;
			}

			$report[] = [
				'error'      => $error,
				'subject'    => $msg->subject,
				'date'       => $msg->date,
				'uid'        => $msg->uid,
				'message_id' => $msg->message_id,
				'mailing_id' => $id,
				'from'       => $msg->from->address,
				'sender'     => $msg->sender->address,
				'recipient'  => $this->config->address,
				'provider'   => $this->config->provider,
				'message'    => $this->mailbox->fetchMessage($folder, $msg->uid),
				'flags'      => $msg->flags,
			];
		}
	}
}
