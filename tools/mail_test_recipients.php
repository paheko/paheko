<?php

use KD2\Mail\Mailbox;

$cfg = $_SERVER['argv'][1] ?? null;

if (!$cfg || !is_readable($cfg)) {
	printf("Usage: %s CONFIG_FILE\n", $_SERVER['argv'][0]);
	exit(1);
}

require __DIR__ . '/../src/include/lib/KD2/Mail/Mailbox.php';

$argv = $_SERVER['argv'];
$cmd = $argv[2] ?? null;
$arg = $argv[3] ?? null;

$r = TestMailbox::all($cfg);

if ($arg) {
	$r = find_message($r, $arg);
}

if ($cmd === 'json') {
	echo json_encode($r, JSON_PRETTY_PRINT);
}
elseif ($cmd === 'headers' && $arg) {
	echo $r->headers ?? "Not found\n";
}
elseif ($cmd === 'body' && $arg) {
	echo $r->body ?? "Not found\n";
}
elseif ($cmd === 'export' && $arg) {
	echo $r->headers . "\r\n\r\n" . $r->body;
}
else {
	foreach ($r as $address => $messages) {
		echo "--- $address ---\n";

		foreach ($messages as $msg) {
			display_message_report($msg, false);
		}
	}
}

function display_message_report(stdClass $msg)
{
	echo '[';

	if ($msg->status === 'inbox') {
		color('green', 'INBOX');
	}
	else {
		color('red', strtoupper($msg->status));
	}

	echo '] ' . $msg->subject . "\n";

	printf("  From: %s\n  Date: %s\n  Message-ID: %s\n  Flags: %s\n  Local ID: %s\n", $msg->from, $msg->date, $msg->message_id, implode(' ', $msg->flags), $msg->id);

	if ($msg->status !== 'inbox') {
		echo "  Diagnostic headers:\n";

		foreach ($msg->diagnostic as $header) {
			echo '  ' . $header . "\n";
		}
	}

	foreach ($msg->actions as $action) {
		color('yellow', sprintf("  -> %s\n", $action));
	}
}

function color(string $color, string $str): void
{
	static $codes = [
		'red' => 91,
		'green' => 92,
		'yellow' => 93,
	];

	printf("\e[%dm%s\e[0m", $codes[$color], $str);
}

function find_message(array $report, string $requested): ?stdClass
{
	foreach ($report as $address => $messages) {
		foreach ($messages as $id => $msg) {
			if ($id === $requested) {
				return $msg;
			}
		}
	}

	return null;
}

class TestMailbox
{
	const JUNK_FOLDER_NAMES = [
		// Orange/SFR/Neuf
		'QUARANTINE',
		// Laposte.net
		'QUARANTAINE',
		// Yahoo.fr = Bulk Mail
		'Bulk Mail',
		// Most others
		'Junk',
		'Spam',
	];

	const PROMO_FLAGS = [
		// SFR / Laposte.net = les messages identifiés comme "NEWSLETTER" sont mis dans le dossier
		// "Infos et promos"
		'NEWSLETTER',
	];

	const SPAM_HEADERS_REGEXP =
		// Most VadeRetro headers (OVH, Laposte...)
		'/^(?:X-[a-z]+-(?:mailing|spamrating|spamlevel|spamcause)'
		. '|X-[^:]*spam[^:]*'
		. '|X-(?:ironport|vr)-[^:]+'
		. '):.*$/mi';

	protected stdClass $config;
	protected Mailbox $mailbox;

	static public function all(string $file, ?DateTime $since = null): array
	{
		$ini = parse_ini_file($file, true);

		$defaults = [
			'user'              => null,
			'provider'          => null,
			'address'           => null,
			'mark_as_important' => true,
			'move_if_spam'      => true,
		];

		$reports = [];

		foreach ($ini as $address => $config) {
			$config = (object) array_merge($defaults, $config);
			$config->address = $address;
			$config->user ??= $address;
			$config->provider ??= substr($address, strrpos($address, '@')+1);

			$t = new self($config);
			$reports[$address] = $t->report($since);
		}

		return $reports;
	}

	public function __construct(stdClass $config)
	{
		$this->config = $config;
	}

	public function report(?DateTime $since = null): array
	{
		$m = $this->mailbox = new Mailbox($this->config->imap);
		//$m->setLogFilePointer(STDOUT);
		$m->setLogin($this->config->user, $this->config->password ?? null, $this->config->token ?? null);
		$this->config->password = null;
		$this->config->token = null;
		$folders = $m->listFolders();
		$report = [];

		foreach ($folders as $key => $folder) {
			if (in_array('Junk', $folder->flags)
				|| $key === 'INBOX'
				|| in_array($key, self::JUNK_FOLDER_NAMES)) {
				$this->exploreFolder($key, $since, $report);
			}
			else {
				// Not interested
				continue;
			}
		}

		return $report;
	}

	protected function exploreFolder(string $folder, ?DateTime $since, array &$report)
	{
		$since ??= new \DateTime('30 days ago');

		foreach ($this->mailbox->listMessages($folder, ['since' => $since], ['X-Is-Recipient']) as $msg) {
			// Not one of our messages
			if (false === strpos($msg->headers, 'X-Is-Recipient:')) {
				continue;
			}

			$status = null;

			// Identify messages marked as promo (SFR/Laposte)
			if ($folder === 'INBOX'
				&& in_array(self::PROMO_FLAGS, $msg->flags)) {
				$status = 'promotional';
			}
			elseif ($folder !== 'INBOX') {
				$status = 'junk';
			}
			elseif ($folder === 'INBOX') {
				$status = 'inbox';
			}

			$raw = $this->mailbox->fetchMessage($folder, $msg->uid);
			$parts = explode("\r\n\r\n", $raw, 2);

			preg_match_all(self::SPAM_HEADERS_REGEXP, $parts[0], $match);
			$spam_headers = array_map('trim', $match[0]);

			$r = (object) [
				'id'         => sha1($this->config->address . $msg->message_id),
				'status'     => $status,
				'folder'     => $folder,
				'subject'    => $msg->subject,
				'date'       => $msg->date->format('Y-m-d H:i:s'),
				'uid'        => $msg->uid,
				'message_id' => $msg->message_id,
				'from'       => $msg->from->address,
				'sender'     => $msg->sender->address,
				'recipient'  => $this->config->address,
				'provider'   => $this->config->provider,
				'flags'      => $msg->flags,
				'diagnostic' => $spam_headers,
				'headers'    => $parts[0],
				'body'       => $parts[1],
				'size'       => strlen($raw),
				'actions'    => null,
			];

			unset($raw, $parts);

			$actions = [];

			if ($this->config->mark_as_important) {
				$actions[] = $this->addFlagsToMessage($r, ['\Seen', '\Flagged']);

				if ($this->config->provider === 'gmail.com') {
					$actions[] = $this->addFlagsToMessage($r, ['NotJunk', 'Important']);
					$this->mailbox->move($folder, $msg->uid, '[Gmail]/Important');
					$actions[] = 'Moved to "Important" folder';
				}
			}
			elseif ($status === 'junk' && $this->config->move_if_spam) {
				$actions[] = $this->removeFlagsFromMessage($r, ['\Junk', 'Junk', 'Spam']);
				$this->mailbox->move($folder, $msg->uid, 'INBOX');
				$actions[] = 'Moved to INBOX';
			}
			elseif ($status === 'promotional' && $this->config->move_if_spam) {
				$actions[] = $this->removeFlagsFromMessage($r, self::PROMO_FLAGS);
			}

			$actions = array_filter($actions);
			$r->actions = $actions;

			$report[$r->id] = $r;
		}

		return $report;
	}

	protected function removeFlagsFromMessage(stdClass $msg, array $flags): ?string
	{
		$applied = [];
		foreach ($flags as $flag) {
			if (!in_array($flag, $msg->flags)
				&& !in_array(ltrim($flag, '\\'), $msg->flags)) {
				continue;
			}

			$this->mailbox->removeFlag($msg->folder, $msg->uid, $flag);
			$applied[] = $flag;
		}

		if (count($applied)) {
			return 'Removed flags: ' . implode(' ', $applied);
		}

		return null;
	}

	protected function addFlagsToMessage(stdClass $msg, array $flags): ?string
	{
		$applied = [];
		foreach ($flags as $flag) {
			if (in_array($flag, $msg->flags)
				|| in_array(ltrim($flag, '\\'), $msg->flags)) {
				continue;
			}

			$this->mailbox->addFlag($msg->folder, $msg->uid, $flag);
			$applied[] = $flag;
		}

		if (count($applied)) {
			return 'Add flags: ' . implode(' ', $applied);
		}

		return null;
	}
}
