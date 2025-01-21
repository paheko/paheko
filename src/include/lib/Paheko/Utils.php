<?php

namespace Paheko;

use KD2\Security;
use KD2\Form;
use KD2\HTTP;
use KD2\Translate;
use KD2\SMTP;
use KD2\DB\DB_Exception;

use Paheko\Users\Session;

class Utils
{
	static protected $collator;
	static protected $transliterator;

	const ICONS = [
		'up'              => '↑',
		'down'            => '↓',
		'export'          => '↷',
		'import'          => '↶',
		'reset'           => '↺',
		'upload'          => '⇑',
		'download'        => '⇓',
		'home'            => '⌂',
		'print'           => '⎙',
		'star'            => '★',
		'check'           => '☑',
		'settings'        => '☸',
		'alert'           => '⚠',
		'mail'            => '✉',
		'edit'            => '✎',
		'delete'          => '✘',
		'help'            => '❓',
		'plus'            => '➕',
		'minus'           => '➖',
		'login'           => '⇥',
		'logout'          => '⤝',
		'eye-off'         => '⤫',
		'menu'            => '𝍢',
		'eye'             => '👁',
		'user'            => '👤',
		'users'           => '👪',
		'calendar'        => '📅',
		'attach'          => '📎',
		'search'          => '🔍',
		'lock'            => '🔒',
		'unlock'          => '🔓',
		'folder'          => '🗀',
		'document'        => '🗅',
		'bold'            => 'B',
		'italic'          => 'I',
		'header'          => 'H',
		'text'            => 'T',
		'paragraph'       => '§',
		'list-ol'         => '1',
		'list-ul'         => '•',
		'table'           => '◫',
		'radio-unchecked' => '◯',
		'uncheck'         => '☐',
		'radio-checked'   => '⬤',
		'image'           => '🖻',
		'left'            => '←',
		'right'           => '→',
		'column'          => '▚',
		'del-column'      => '🮔',
		'reload'          => '🗘',
		'gallery'         => '🖼',
		'code'            => '<',
		'markdown'        => 'M',
		'globe'           => '🌍',
		'video'           => '▶',
		'quote'           => '«',
		'money'           => '€',
		'pdf'             => 'P',
		'trash'           => '🗑',
		'history'         => '⌚',
		'link'            => '🔗',
		'chat'            => '💬',
		'smile'           => '☺',
		'camera'          => '📷',
		'videocam'        => '📹',
		'microphone'      => '🎤',
		'barcode'         => '│',
	];

	const FRENCH_DATE_NAMES = [
		'January'   => 'janvier',
		'February'  => 'février',
		'March'     => 'mars',
		'April'     => 'avril',
		'May'       => 'mai',
		'June'      => 'juin',
		'July'      => 'juillet',
		'August'    => 'août',
		'September' => 'septembre',
		'October'   => 'octobre',
		'November'  => 'novembre',
		'December'  => 'décembre',
		'Monday'    => 'lundi',
		'Tuesday'   => 'mardi',
		'Wednesday' => 'mercredi',
		'Thursday'  => 'jeudi',
		'Friday'    => 'vendredi',
		'Saturday'  => 'samedi',
		'Sunday'    => 'dimanche',
		'Jan' => 'jan',
		'Feb' => 'fév',
		'Mar' => 'mar',
		'Apr' => 'avr',
		'Jun' => 'juin',
		'Jul' => 'juil',
		'Aug' => 'août',
		'Sep' => 'sep',
		'Oct' => 'oct',
		'Nov' => 'nov',
		'Dec' => 'déc',
		'Mon' => 'lun',
		'Tue' => 'mar',
		'Wed' => 'mer',
		'Thu' => 'jeu',
		'Fri' => 'ven',
		'Sat' => 'sam',
		'Sun' => 'dim',
	];

	static public function get_datetime($ts)
	{
		if (null === $ts) {
			return null;
		}

		if (is_object($ts) && $ts instanceof \DateTimeInterface) {
			return $ts;
		}
		elseif (is_numeric($ts)) {
			$ts = new \DateTime('@' . $ts);
			$ts->setTimezone(new \DateTimeZone(date_default_timezone_get()));
			return $ts;
		}
		elseif (preg_match('!^\d{2}/\d{2}/\d{4}$!', $ts)) {
			return \DateTime::createFromFormat('!d/m/Y', $ts);
		}
		elseif (preg_match('!^\d{2}/\d{2}/\d{2}$!', $ts)) {
			return \DateTime::createFromFormat('!d/m/y', $ts);
		}
		elseif (strlen($ts) == 10) {
			return \DateTime::createFromFormat('!Y-m-d', $ts);
		}
		elseif (strlen($ts) == 19) {
			return \DateTime::createFromFormat('Y-m-d H:i:s', $ts);
		}
		elseif (strlen($ts) == 16) {
			return \DateTime::createFromFormat('!Y-m-d H:i', $ts);
		}
		else {
			return null;
		}
	}

	static public function strftime_fr($ts, $format): string
	{
		$ts = self::get_datetime($ts);

		if (null === $ts) {
			return '';
		}

		$date = Translate::strftime($format, $ts, 'fr_FR');
		return $date;
	}

	static public function date_fr($ts, $format = null): string
	{
		$ts = self::get_datetime($ts);

		if (!$ts) {
			return '';
		}

		if (empty($format)) {
			$format = 'd/m/Y à H:i';
		}

		$date = $ts->format($format);

		$date = strtr($date, self::FRENCH_DATE_NAMES);
		return $date;
	}

	static public function shortDate($ts, bool $with_hour = false): ?string
	{
		return self::date_fr($ts, 'd/m/Y' . ($with_hour ? ' à H\hi' : ''));
	}

	/**
	 * @deprecated
	 */
	static public function checkDate($str)
	{
		if (!preg_match('!^(\d{4})-(\d{2})-(\d{2})$!', $str, $match))
			return false;

		if (!checkdate($match[2], $match[3], $match[1]))
			return false;

		return true;
	}

	/**
	 * @deprecated
	 */
	static public function checkDateTime($str)
	{
		if (!preg_match('!^(\d{4}-\d{2}-\d{2})[T ](\d{2}):(\d{2})!', $str, $match))
			return false;

		if (!self::checkDate($match[1]))
			return false;

		if ((int) $match[2] < 0 || (int) $match[2] > 23)
			return false;

		if ((int) $match[3] < 0 || (int) $match[3] > 59)
			return false;

		if (isset($match[4]) && ((int) $match[4] < 0 || (int) $match[4] > 59))
			return false;

		return true;
	}

	static public function moneyToInteger($value)
	{
		if (null === $value || trim($value) === '') {
			return 0;
		}

		// Remove whitespace characters
		$value = preg_replace('/\h/u', '', $value);

		// Remove US thousands separator, if cents separator is a point
		if (strpos($value, '.') === strlen($value) - 3) {
			$value = str_replace(',', '', $value);
		}

		if (!preg_match('/^(-?)(\d+)(?:[,.](\d{1,3}))?/', $value, $match)) {
			throw new UserException(sprintf('Le montant est invalide : %s. Exemple de format accepté : 142,02', $value));
		}

		$cents = $match[3] ?? '0';

		if (strlen($cents) === 1) {
			$cents .= '0';
		}

		$more = (int) substr($cents, 2, 1);
		$cents = (int) substr($cents, 0, 2);

		if ($more >= 5) {
			$cents++;
		}

		$value = $match[1] . $match[2] . str_pad((string)$cents, 2, '0', STR_PAD_LEFT);
		$value = (int) $value;
		return $value;
	}

	static public function weightToInteger($number): ?int
	{
		if (null === $number || '' === $number) {
			return null;
		}

		if (-1 == $number) {
			return -1;
		}

		$w = explode('.', str_replace(',', '.', $number));
		$a = $w[0] ?: '0';
		$b = substr(($w[1] ?? '0') . '000', 0, 3);
		return intval($a . $b);
	}

	static public function format_weight($number, bool $empty_is_zero = false, bool $append_unit = false): string
	{
		if (empty($number) || $number < 0) {
			return $empty_is_zero ? '0' : '';
		}

		$decimals = substr($number, -3);

		if ((int)$decimals > 0) {
			$decimals = ',' . substr('000' . $decimals, -3);
		}
		else {
			$decimals = '';
		}

		$out = (int)substr($number, 0, -3) . $decimals;

		if ($append_unit) {
			$out .= ' kg';
		}

		return $out;
	}

	static public function money_format($number, ?string $dec_point = ',', string $thousands_sep = ' ', $zero_if_empty = true): string {
		if ($number == 0) {
			return $zero_if_empty ? '0' : '0,00';
		}

		$sign = $number < 0 ? '-' : '';

		// Convert floats to string, and THEN to integer
		// to avoid truncating numbers
		// see https://fossil.kd2.org/paheko/tktview/a29df35328fdf783b98edb60f038f248c3af9b38
		if (!is_int($number)) {
			$number = (int)(string)$number;
		}

		$number = abs((int)(string) $number);

		$decimals = substr('0' . $number, -2);
		$number = (int) substr($number, 0, -2);

		if ($dec_point === null) {
			$decimals = null;
		}

		return sprintf('%s%s%s%s', $sign, number_format($number, 0, $dec_point, $thousands_sep), $dec_point, $decimals);
	}

	static public function getLocalURL(string $url = '', ?string $default_prefix = null): string
	{
		if (substr($url, 0, 1) == '!') {
			return ADMIN_URL . substr($url, 1);
		}
		elseif (substr($url, 0, 7) == '/admin/') {
			return ADMIN_URL . substr($url, 7);
		}
		elseif (substr($url, 0, 2) == './') {
			$base = self::getSelfURI();
			$base = preg_replace('!/[^/]*$!', '/', $base);
			$base = trim($base, '/');
			$base = $base ? $base . '/' : '';
			return '/' . $base . substr($url, 2);
		}
		elseif (substr($url, 0, 1) == '/' && strpos($url, WWW_URI) === 0) {
			return WWW_URL . substr($url, strlen(WWW_URI));
		}
		elseif (substr($url, 0, 1) == '/') {
			return WWW_URL . substr($url, 1);
		}
		elseif (substr($url, 0, 5) == 'http:' || substr($url, 0, 6) == 'https:') {
			return $url;
		}
		elseif ($url == '') {
			return ADMIN_URL;
		}
		else {
			if (null !== $default_prefix) {
				$default_prefix = self::getLocalURL($default_prefix);
			}

			return $default_prefix . $url;
		}
	}

	static public function getRequestURI(): ?string
	{
		if (!empty($_SERVER['REQUEST_URI']))
			return $_SERVER['REQUEST_URI'];
		else
			return null;
	}

	static public function getSelfURL($qs = true)
	{
		$uri = self::getSelfURI($qs);

		// Make absolute URI relative to parent URI
		if (0 === strpos($uri, WWW_URI . 'admin/')) {
			$uri = substr($uri, strlen(WWW_URI . 'admin/'));
		}

		return ADMIN_URL . ltrim($uri, '/');
	}

	static public function getSelfURI($qs = true)
	{
		$uri = self::getRequestURI();

		if ($qs !== true && (strpos($uri, '?') !== false))
		{
			$uri = substr($uri, 0, strpos($uri, '?'));
		}

		if (is_array($qs))
		{
			$uri .= '?' . http_build_query($qs);
		}

		return $uri;
	}

	static public function getModifiedURL(string $new)
	{
		return HTTP::mergeURLs(self::getSelfURI(), $new);
	}

	static public function getTrustedURL(?string $url): ?string
	{
		if (!$url) {
			return null;
		}

		$url = rawurldecode($url);

		// If URL contains another URL, something is weird, abort
		if (false !== strpos(substr($url, 8), '://')) {
			return null;
		}

		if (0 === strpos($url, ADMIN_URL)) {
			return $url;
		}
		elseif (0 === strpos($url, WWW_URL)) {
			return $url;
		}
		// Other external domain: not allowed
		elseif (false !== strpos($url, '//')) {
			return null;
		}
		elseif (0 === strpos($url, 'admin/')) {
			return ADMIN_URL . substr($url, 6);
		}
		else {
			return WWW_URL . ltrim($url, '/');
		}
	}

	static public function getDialogTarget(): ?string
	{
		$dialog = $_GET['_dialog'] ?? null;

		if (null === $dialog) {
			return null;
		}

		if ($dialog !== '' && !ctype_alnum($dialog)) {
			return null;
		}

		return $dialog ?: '1';
	}

	static public function redirectSelf(?string $destination = null, bool $exit = true): void
	{
		self::redirect($destination, $exit);
	}

	static public function redirectParent(?string $destination = null, bool $exit = true): void
	{
		self::redirectDialog($destination, $exit);
	}

	static public function redirectDialog(?string $destination = null, bool $exit = true): void
	{
		if (isset($_GET['_dialog'])) {
			self::reloadParentFrame($destination, $exit);
		}
		else {
			self::redirect($destination, $exit);
		}
	}

	static public function reloadSelfFrame(?string $destination = null, bool $exit = true): void
	{
		if (!Utils::getDialogTarget()) {
			self::redirect($destination, $exit);
		}

		$url = self::getLocalURL($destination ?? '!');
		$js = 'location.href = window.parent.g.dialog.querySelector("iframe").getAttribute("src");';

		echo self::getFrameRedirectHTML($js, $url);

		if ($exit) {
			exit;
		}
	}

	static public function reloadParentFrameIfDialog(?string $destination = null): void
	{
		if (!isset($_GET['_dialog'])) {
			return;
		}

		self::reloadParentFrame($destination);
	}

	static public function reloadParentFrame(?string $destination = null, bool $exit = true): void
	{
		$url = self::getLocalURL($destination ?? '!');

		if (null === $destination) {
			$js = 'window.parent.location.reload();';
		}
		else {
			$js = sprintf('window.parent.location.href = %s;', json_encode($url));
		}

		echo self::getFrameRedirectHTML($js, $url);

		if ($exit) {
			exit;
		}
	}

	static public function getFrameRedirectHTML(string $js, string $url): string
	{
		return '
			<!DOCTYPE html>
			<html>
			<head>
				<script type="text/javascript">
				if (window.top !== window) {
					document.write(\'<style type="text/css">p { display: none; }</style>\');
					' . $js . '
				}
				</script>
			</head>

			<body>
			<p><a href="' . htmlspecialchars($url) . '">Cliquer ici pour continuer</a>
			</body>
			</html>';
	}

	public static function redirect(?string $destination = null, bool $exit = true)
	{
		$destination ??= '';
		$destination = self::getLocalURL($destination);

		if (isset($_GET['_dialog'])) {
			$destination .= (strpos($destination, '?') === false ? '?' : '&') . '_dialog';

			if (!empty($_GET['_dialog'])) {
				$destination .= '=' . rawurlencode($_GET['_dialog']);
			}
		}

		if (PHP_SAPI == 'cli') {
			echo 'Please visit ' . $destination . PHP_EOL;
			exit;
		}

		if (headers_sent())
		{
			echo
			  '<html>'.
			  ' <head>' .
			  '  <script type="text/javascript">' .
			  '    document.location = "' . htmlspecialchars($destination, ENT_QUOTES, 'UTF-8', false) . '";' .
			  '  </script>' .
			  ' </head>'.
			  ' <body>'.
			  '   <div>'.
			  '     <a href="' . htmlspecialchars($destination, ENT_QUOTES, 'UTF-8', false) . '">Cliquez ici pour continuer...</a>'.
			  '   </div>'.
			  ' </body>'.
			  '</html>';

			if ($exit)
			  exit();

			return true;
		}

		header("Location: " . $destination);

		if ($exit) {
			exit;
		}
	}

	static public function getIP()
	{
		if (!empty($_SERVER['REMOTE_ADDR']))
			return $_SERVER['REMOTE_ADDR'];
		return '';
	}

	static public function getCountryList()
	{
		return Translate::getCountriesList('fr');
	}

	static public function getCountryName(string $code): ?string
	{
		$code = strtoupper($code);
		$list = self::getCountryList();
		return $list[$code] ?? null;
	}

	static public function getCountryCode(string $find): ?string
	{
		if (!strlen($find)) {
			return null;
		}

		$list = self::getCountryList();

		foreach ($list as $code => $name) {
			if (strnatcasecmp($find, $name) === 0) {
				return $code;
			}
		}

		return null;
	}

	static public function transliterateToAscii($str, $charset='UTF-8')
	{
		// Don't process empty strings
		if (!trim($str))
			return $str;

		// We only process non-ascii strings
		if (preg_match('!^[[:ascii:]]+$!', $str))
			return $str;

		$str = htmlentities($str, ENT_NOQUOTES, $charset);

		$str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
		$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'

		$str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
		$str = preg_replace('![^[:ascii:]]+!', '', $str);

		return $str;
	}

	static public function safe_unlink(string $path): bool
	{
		if (!@unlink($path))
		{
			return true;
		}

		if (!file_exists($path))
		{
			return true;
		}

		throw new \RuntimeException(sprintf('Impossible de supprimer le fichier %s: %s', $path, error_get_last()));

		return true;
	}

	static public function safe_mkdir($path, $mode = null, $recursive = false)
	{
		if (null === $mode && file_exists(DATA_ROOT)) {
			$mode = fileperms(DATA_ROOT);
		}
		elseif (null === $mode && file_exists(ROOT)) {
			$mode = fileperms(ROOT);
		}

		return @mkdir($path, $mode, $recursive) || is_dir($path);
	}

	/**
	 * Does a recursive list using glob(), this is faster than using Recursive iterators
	 * @param  string $path    Target path
	 * @param  string $pattern Pattern
	 * @param  int    $flags   glob() Flags
	 * @return array
	 */
	static public function recursiveGlob(string $path, string $pattern = '*', int $flags = 0): array
	{
		$target = $path . DIRECTORY_SEPARATOR . $pattern;
		$list = [];

		// glob is the fastest way to recursely list directories and files apparently
		// after comparing with opendir(), dir() and filesystem recursive iterators
		foreach(glob($target, $flags) as $file) {
			$file = basename($file);

			if ($file[0] == '.') {
				continue;
			}

			$list[] = $file;

			if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
				foreach (self::recursiveGlob($path . DIRECTORY_SEPARATOR . $file, $pattern, $flags) as $subfile) {
					$list[] = $file . DIRECTORY_SEPARATOR . $subfile;
				}
			}
		}

		return $list;
	}

	static public function suggestPassword(): string
	{
		return Security::getRandomPassphrase(ROOT . '/include/data/locales/fr/dictionary.txt');
	}

	/**
	 * Validate URL, allowing IDN domain names
	 */
	static public function validateURL(string $url): bool
	{
		$url = parse_url($url);

		if (empty($url['host']) || empty($url['scheme'])) {
			return false;
		}

		$url['host'] = idn_to_ascii($url['host']);
		$n = $url['scheme'] . '://' . $url['host'];

		if (!empty($url['port'])
			&& !($url['port'] == 80 && $url['scheme'] === 'http')
			&& !($url['port'] == 443 && $url['scheme'] === 'https')) {
			$n .= ':' . $url['port'];
		}

		$n .= $url['path'] ?? '/';

		if (!empty($url['query'])) {
			$n .= '?' . $url['query'];
		}

		return filter_var($n, FILTER_VALIDATE_URL) !== false;
	}

	static public function normalizePhoneNumber(string $n): string
	{
		return preg_replace('![^\d\+\(\)p#,;-]!', '', trim($n));
	}

	static public function normalizeSIRET(string $n): string
	{
		$n = trim($n);
		$n = str_replace(' ', '', $n);
		return $n;
	}

	static public function checkSIRET(string $n): bool
	{
		$n = self::normalizeSIRET($n);

		if (strlen($n) !== 14) {
			return false;
		}

		if (!ctype_digit($n)) {
			return false;
		}

		$sum = 0;

		for ($i = 0; $i < 14; ++$i) {
			if ($i % 2 === 0) {
				$tmp = ((int) $n[$i]) * 2;
				$tmp = $tmp > 9 ? $tmp - 9 : $tmp;
			} else {
				$tmp = $n[$i];
			}

			$sum += $tmp;
		}

		return !($sum % 10 !== 0);
	}

	static public function write_ini_string($in)
	{
		$out = '';
		$get_ini_line = function ($key, $value) use (&$get_ini_line)
		{
			if (is_bool($value))
			{
				return $key . ' = ' . ($value ? 'true' : 'false');
			}
			elseif (is_numeric($value))
			{
				return $key . ' = ' . $value;
			}
			elseif (is_array($value) || is_object($value))
			{
				$out = '';
				$value = (array) $value;
				foreach ($value as $row)
				{
					$out .= $get_ini_line($key . '[]', $row) . "\n";
				}

				return substr($out, 0, -1);
			}
			else
			{
				return $key . ' = "' . str_replace('"', '\\"', $value) . '"';
			}
		};

		foreach ($in as $key=>$value)
		{
			if ((is_array($value) || is_object($value)) && is_string($key))
			{
				$out .= '[' . $key . "]\n";

				foreach ($value as $row_key=>$row_value)
				{
					$out .= $get_ini_line($row_key, $row_value) . "\n";
				}

				$out .= "\n";
			}
			else
			{
				$out .= $get_ini_line($key, $value) . "\n";
			}
		}

		return $out;
	}

	static public function getMaxUploadSize()
	{
		$limits = [
			self::return_bytes(ini_get('upload_max_filesize')),
			self::return_bytes(ini_get('post_max_size'))
		];

		return min(array_filter($limits));
	}


	static public function return_bytes($size_str)
	{
		if ($size_str == '-1')
		{
			return false;
		}

		if (PHP_VERSION_ID >= 80200) {
			return ini_parse_quantity($size_str);
		}

		switch (substr($size_str, -1))
		{
			case 'G': case 'g': return (int)$size_str * pow(1024, 3);
			case 'M': case 'm': return (int)$size_str * pow(1024, 2);
			case 'K': case 'k': return (int)$size_str * 1024;
			default: return $size_str;
		}
	}

	static public function format_bytes($size, bool $bytes = false)
	{
		if ($size > (1024 * 1024 * 1024 * 1024)) {
			$size = $size / 1024 / 1024 / 1024 / 1024;

			if ($size < 10) {
				$decimals = $size == (int) $size ? 0 : 1;
				return number_format(round($size, 1), $decimals, ',', '') . ' To';
			}
			else {
				return round($size) . ' To';
			}
		}
		elseif ($size > (1024 * 1024 * 1024)) {
			$size = $size / 1024 / 1024 / 1024;

			if ($size < 10) {
				$decimals = $size == (int) $size ? 0 : 1;
				return number_format(round($size, 1), $decimals, ',', '') . ' Go';
			}
			else {
				return ceil($size) . ' Go';
			}
		}
		elseif ($size > (1024 * 1024)) {
			$size = $size / 1024 / 1024;
			return ceil($size) . ' Mo';
		}
		elseif ($size > 1024) {
			return ceil($size / 1024) . ' Ko';
		}
		elseif ($bytes) {
			return $size . ' octets';
		}
		elseif (!$size) {
			return '0 o';
		}
		else {
			return '< 1 Ko';
		}
	}

	static public function createEmptyDirectory(string $path)
	{
		Utils::safe_mkdir($path, 0777, true);

		if (!is_dir($path))
		{
			throw new UserException('Le répertoire '.$path.' n\'existe pas ou n\'est pas un répertoire.');
		}

		// On en profite pour vérifier qu'on peut y lire et écrire
		if (!is_writable($path) || !is_readable($path))
		{
			throw new UserException('Le répertoire '.$path.' n\'est pas accessible en lecture/écriture.');
		}

		// Some basic safety against misconfigured hosts
		file_put_contents($path . '/index.html', '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>');
	}

	static public function resetCache(string $path): void
	{
		if (!file_exists($path)) {
			self::createEmptyDirectory($path);
			return;
		}

		$dir = dir($path);

		while ($file = $dir->read()) {
			if (substr($file, 0, 1) == '.' || is_dir($path . DIRECTORY_SEPARATOR . $file)) {
				continue;
			}

			self::safe_unlink($path . DIRECTORY_SEPARATOR . $file);
		}

		$dir->close();
	}

	static public function deleteRecursive(string $path, bool $delete_self = false): bool
	{
		if (!file_exists($path)) {
			return false;
		}

		if (is_file($path)) {
			return self::safe_unlink($path);
		}

		$dir = dir($path);
		if (!$dir) return false;

		while ($file = $dir->read())
		{
			if ($file == '.' || $file == '..')
				continue;

			if (is_dir($path . DIRECTORY_SEPARATOR . $file))
			{
				if (!self::deleteRecursive($path . DIRECTORY_SEPARATOR . $file, true))
					return false;
			}
			else
			{
				self::safe_unlink($path . DIRECTORY_SEPARATOR . $file);
			}
		}

		$dir->close();

		if ($delete_self) {
			rmdir($path);
		}

		return true;
	}

	static public function plugin_url($params = [])
	{
		if (isset($params['id']))
		{
			$url = ADMIN_URL . 'p/' . $params['id'] . '/';
		}
		elseif (defined('Paheko\PLUGIN_ADMIN_URL'))
		{
			$url = PLUGIN_ADMIN_URL;
		}
		else {
			throw new \RuntimeException('Missing plugin URL');
		}

		if (!empty($params['file']))
			$url .= $params['file'];

		if (!empty($params['query']))
		{
			$url .= '?';

			if (!(is_numeric($params['query']) && (int)$params['query'] === 1) && $params['query'] !== true)
				$url .= $params['query'];
		}

		return $url;
	}

	static public function iconUnicode(string $shape): string
	{
		if (!isset(self::ICONS[$shape])) {
			throw new \UnexpectedValueException('Unknown icon shape: ' . $shape);
		}

		return self::ICONS[$shape];
	}

	static public function array_transpose(?array $array): array
	{
		$out = [];

		if (!$array) {
			return $out;
		}

		$max = 0;

		foreach ($array as $rows) {
			if (!is_array($rows)) {
				throw new \UnexpectedValueException('Invalid multi-dimensional array: not an array: ' . gettype($rows));
			}

			$max = max($max, count($rows));
		}

		foreach ($array as $column => $rows) {
			// Match number of rows of largest sub-array, in case there is a missing row in a column
			if ($max != count($rows)) {
				$rows = array_merge($rows, array_fill(0, $max - count($rows), null));
			}

			foreach ($rows as $k => $v) {
				if (!isset($out[$k])) {
					$out[$k] = [];
				}

				$out[$k][$column] = $v;
			}
		}

		return $out;
	}

	static public function rgbHexToDec(string $hex)
	{
		return sscanf($hex, '#%02x%02x%02x');
	}

	static public function rgbDecToHex(array $rgb)
	{
		foreach ($rgb as &$v) {
			if ($v > 255) {
				$v = 255;
			}
			elseif ($v < 0) {
				$v = 0;
			}
		}

		unset($v);

		return vsprintf('#%02x%02x%02x', $rgb);
	}

	/**
	 * Converts an RGB color value to HSV. Conversion formula
	 * adapted from http://en.wikipedia.org/wiki/HSV_color_space.
	 * Assumes r, g, and b are contained in the set [0, 255] and
	 * returns h, s, and v in the set [0, 1].
	 *
	 * @param   int|string  r       The red color value
	 * @param   int|null  g       The green color value
	 * @param   int|null  b       The blue color value
	 * @return  array           The HSV representation
	 */
	static public function rgbToHsv($r, $g = null, $b = null): array
	{
		if (is_string($r) && is_null($g) && is_null($b))
		{
			list($r, $g, $b) = self::rgbHexToDec($r);
		}

		$r /= 255;
		$g /= 255;
		$b /= 255;
		$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$h = $s = $v = $max;

		$d = $max - $min;
		//$s = ($max == 0) ? 0 : $d / $max;
		$l = ($max + $min) / 2;
		$s = $l > 0.5 ? $d / ((2 - $max - $min) ?: 1) : $d / (($max + $min) ?: 1);

		if($max == $min)
		{
			$h = 0; // achromatic
		}
		else
		{
			switch($max)
			{
				case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
				case $g: $h = ($b - $r) / $d + 2; break;
				case $b: $h = ($r - $g) / $d + 4; break;
			}
			$h /= 6;
		}

		return array($h * 360, $s, $l);
	}

	static public function HTTPCache(?string $hash, ?int $last_change, int $max_age = 3600, bool $immutable = false): bool
	{
		$etag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH'], '"\' ') : null;
		$last_modified = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : null;

		$etag = $etag ? str_replace('-gzip', '', $etag) : null;

		header(sprintf('Cache-Control: private, max-age=%d%s', $max_age, $immutable ? ', immutable' : ''), true);
		header_remove('Expires');
		header_remove('Pragma');

		if ($last_change) {
			header(sprintf('Last-Modified: %s GMT', gmdate('D, d M Y H:i:s', $last_change)), true);
		}

		if ($hash) {
			$hash = md5(Utils::getVersionHash() . $hash);
			header(sprintf('Etag: "%s"', $hash), true);
		}

		if (($etag && $etag === $hash) || ($last_modified && $last_modified >= $last_change)) {
			http_response_code(304);
			exit;
		}

		return false;
	}

	static public function transformTitleToURI($str)
	{
		$str = Utils::transliterateToAscii($str);

		$str = preg_replace('![^\w\d_-]!i', '-', $str);
		$str = preg_replace('!-{2,}!', '-', $str);
		$str = trim($str, '-');

		return $str;
	}

	static public function safeFileName(string $str): string
	{
		$str = Utils::transliterateToAscii($str);
		$str = preg_replace('![^\w\d_ -]!i', '.', $str);
		$str = preg_replace('!\.{2,}!', '.', $str);
		$str = trim($str, '.');
		return $str;
	}

	/**
	 * dirname may have undefined behaviour depending on the locale!
	 */
	static public function dirname(string $str): string
	{
		$str = str_replace(DIRECTORY_SEPARATOR, '/', $str);
		return substr($str, 0, strrpos($str, '/'));
	}

	/**
	 * basename may have undefined behaviour depending on the locale!
	 */
	static public function basename(string $str): string
	{
		$str = str_replace(DIRECTORY_SEPARATOR, '/', $str);
		$str = trim($str, '/');
		$str = substr($str, strrpos($str, '/'));
		$str = trim($str, '/');
		return $str;
	}

	static public function unicodeTransliterate($str): ?string
	{
		if ($str === null) {
			return null;
		}

		$str = str_replace('’', '\'', $str); // Normalize French apostrophe

		return transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $str);
	}

	static public function unicodeCaseComparison($a, $b): int
	{
		if (!isset(self::$collator) && function_exists('collator_create')) {
			self::$collator = \Collator::create('fr_FR');

			// This is what makes the comparison case insensitive
			// https://www.php.net/manual/en/collator.setstrength.php
			self::$collator->setAttribute(\Collator::STRENGTH, \Collator::PRIMARY);

			// Don't use \Collator::NUMERIC_COLLATION here as it goes against what would feel logic
			// for account ordering
			// with NUMERIC_COLLATION: 1, 2, 10, 11, 101
			// without: 1, 10, 101, 11, 2
		}

		// Make sure we have UTF-8
		// If we don't, we may end up with malformed database, eg. "row X missing from index" errors
		// when doing an integrity check
		$a = self::utf8_encode($a);
		$b = self::utf8_encode($b);

		$a = mb_substr($a, 0, 100);
		$b = mb_substr($b, 0, 100);

		if (isset(self::$collator)) {
			return (int) self::$collator->compare($a, $b);
		}

		$a = strtoupper(self::transliterateToAscii($a));
		$b = strtoupper(self::transliterateToAscii($b));

		return strcmp($a, $b);
	}

	static public function utf8_encode(?string $str): ?string
	{
		if (null === $str) {
			return null;
		}

		// Check if string is already UTF-8 encoded or not
		if (preg_match('//u', $str)) {
			return $str;
		}

		return !preg_match('//u', $str) ? self::iso8859_1_to_utf8($str) : $str;
	}

	/**
	 * Poly-fill to encode a ISO-8859-1 string to UTF-8 for PHP >= 9.0
	 * @see https://php.watch/versions/8.2/utf8_encode-utf8_decode-deprecated
	 */
	static public function iso8859_1_to_utf8(string $s): string
	{
		if (PHP_VERSION_ID < 90000) {
			return @utf8_encode($s);
		}

		$s .= $s;
		$len = strlen($s);

		for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
			switch (true) {
				case $s[$i] < "\x80":
					$s[$j] = $s[$i];
					break;
				case $s[$i] < "\xC0":
					$s[$j] = "\xC2";
					$s[++$j] = $s[$i];
					break;
				default:
					$s[$j] = "\xC3";
					$s[++$j] = chr(ord($s[$i]) - 64);
					break;
			}
		}

		return substr($s, 0, $j);
	}

	/**
	 * Transforms a unicode string to lowercase AND removes all diacritics
	 *
	 * @see https://www.matthecat.com/supprimer-les-accents-d-une-chaine-avec-php.html
	 */
	static public function unicodeCaseFold(?string $str): string
	{
		if (null === $str || trim($str) === '') {
			return '';
		}

		$str = str_replace('’', '\'', $str); // Normalize French apostrophe

		if (!isset(self::$transliterator) && function_exists('transliterator_create')) {
			self::$transliterator = \Transliterator::create('Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; Lower();');
		}

		if (isset(self::$transliterator)) {
			return self::$transliterator->transliterate($str);
		}

		return strtoupper(self::transliterateToAscii($str));
	}

	static public function knatcasesort(array $array)
	{
		uksort($array, [self::class, 'unicodeCaseComparison']);
		return $array;
	}

	static public function appendCookieToURLs(string $str): string
	{
		$cookie = Session::getCookie();
		$secret = Session::getCookieSecret();

		if (!$cookie) {
			return $str;
		}

		// Append session cookie to URLs, so that <img> tags and others work
		$r = preg_quote(WWW_URL, '!');
		$r = '!(?<=["\'])((?:/|' . $r . ').*?)(?=["\'])!';
		$str = preg_replace_callback($r, function ($match) use ($cookie, $secret): string {
			if (false !== strpos($match[1], '?')) {
				$separator = '&amp;';
			}
			else {
				$separator = '?';
			}

			if (substr($match[1], 0, 1) === '/') {
				$url = BASE_URL . ltrim($match[1], '/');
			}
			else {
				$url = $match[1];
			}

			return $url . $separator . $cookie . htmlspecialchars($secret);
		}, $str);

		return $str;
	}

	/**
	 * Escape a command-line argument, because escapeshellarg is stripping UTF-8 characters (d'oh)
	 * @see https://markushedlund.com/dev/php-escapeshellarg-with-unicodeutf-8-support/
	 */
	static public function escapeshellarg(string $arg): string
	{
		if (PHP_OS_FAMILY === 'Windows') {
			return '"' . str_replace(array('"', '%'), array('', ''), $arg) . '"';
		}
		else {
			return "'" . str_replace("'", "'\\''", $arg) . "'";
		}
	}

	/**
	 * Execute a system command with a timeout, just returning a string from stdout
	 */
	static public function quick_exec(string $cmd, int $timeout, ?int &$code = null): string
	{
		$output = '';
		$code = self::exec($cmd, $timeout, null, function($data) use (&$output) { $output .= $data; });
		return $output;
	}

	/**
	 * Execute a system command with a timeout, handling STDIN, STDOUT and STDERR with callbacks (asynchronous)
	 *
	 * @var string $cmd Command to run
	 * @var integer $timeout Timeout after which the command should be killed (in seconds)
	 * @var string|null|callable $stdin If this is a callback function, then it will be called until it returns NULL,
	 * and its return will be sent to STDIN. If this is a string, it will be sent as STDIN. If NULL, no STDIN will be sent.
	 * @var null|callable $stdout STDOUT callback, receiving output string as first parameter.
	 * @var null|callable $stdout STDERR callback, receiving error string as first parameter.
	 * @return int Command status code
	 *
	 * @see https://blog.dubbelboer.com/2012/08/24/execute-with-timeout.html
	 */
	static public function exec(string $cmd, int $timeout, $stdin, ?callable $stdout, ?callable $stderr = null): int
	{
		if (!function_exists('proc_open') || !function_exists('proc_terminate')
			|| preg_match('/proc_(?:open|terminate|get_status|close)/', ini_get('disable_functions'))) {
			throw new \RuntimeException('Execution of system commands is disabled.');
		}

		$descriptorspec = [
			0 => ["pipe", "r"], // stdin is a pipe that the child will read from
			1 => ["pipe", "w"], // stdout is a pipe that the child will write to
			2 => ['pipe', 'w'], // stderr
		];

		$process = proc_open($cmd, $descriptorspec, $pipes);

		if (!is_resource($process)) {
			throw new \RuntimeException('Cannot execute command: ' . $cmd);
		}

		// $pipes now looks like this:
		// 0 => writeable handle connected to child stdin
		// 1 => readable handle connected to child stdout

		// Set to non-blocking
		stream_set_blocking($pipes[0], false);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		$timeout_ms = $timeout * 1000000; // in microseconds

		$code = 0;
		$stdin_buffer = null;
		$status = null;

		// Nothing for STDIN, close the pipe
		if (null === $stdin) {
			fclose($pipes[0]);
			$pipes[0] = null;
		}

		while (!$timeout || $timeout_ms > 0) {
			$start = microtime(true);

			// Wait until we can read or write, or until the timer expires
			$read  = [$pipes[1]];
			$write = $pipes[0] !== null ? [$pipes[0]] : null;
			$other = null;

			if (null !== $stderr) {
				$read[] = $pipes[2];
			}

			// Wait every 0.5 seconds
			if (!stream_select($read, $write, $other, 0, 500000)) {
				continue;
			}

			// Write to STDIN
			if ($write && count($write)) {
				if (null === $stdin_buffer) {
					if (is_callable($stdin)) {
						$stdin_buffer = $stdin();
					}
					elseif (null === $stdin) {
						throw new \LogicException('STDIN string has been sent');
					}
					else {
						$stdin_buffer = $stdin;
						$stdin = null;
					}
				}

				// Close pipe if STDIN function returned NULL,
				// or if STDIN string has been written
				$close = $stdin_buffer === null;

				// Progressively write to STDIN, until input is empty
				if (null !== $stdin_buffer) {
					$written = fwrite($pipes[0], $stdin_buffer);

					if ($written !== strlen($stdin_buffer)) {
						$stdin_buffer = substr($stdin_buffer, $written);
					}
					else {
						$close = true;
					}
				}

				if ($close) {
					fclose($pipes[0]);
					$pipes[0] = null;
					$stdin_buffer = null;
				}
			}

			// Get the status of the process.
			// Do this before we read from the stream,
			// this way we can't lose the last bit of output if the process dies between these functions.
			$status = proc_get_status($process);

			// We must get the exit code when it is sent, or we won't be able to get it later
			if ($status['exitcode'] > -1) {
				$code = $status['exitcode'];
			}

			// Read the contents from the buffer.
			// This function will always return immediately as the stream is non-blocking.
			if (in_array($pipes[1], $read, true)) {
				$value = stream_get_contents($pipes[1]);

				if (null !== $stdout) {
					$stdout($value);
				}
			}

			if (in_array($pipes[2], $read, true)) {
				$value = stream_get_contents($pipes[2]);

				if (null !== $stderr) {
					$stderr($value);
				}
			}

			if (!$status['running']) {
				// Break from this loop if the process exited before the timeout.
				break;
			}

			// Subtract the number of microseconds that we waited.
			$timeout_ms -= (microtime(true) - $start) * 1000000;
		}

		fclose($pipes[1]);
		fclose($pipes[2]);

		if ($status['running']) {
			proc_terminate($process, 9);
			throw new \OverflowException(sprintf("Command killed after taking more than %d seconds: \n%s", $timeout, $cmd));
		}

		proc_close($process);

		return $code;
	}

	static protected function getPrinceCommand(): string
	{
		$org_name = Config::getInstance()->org_name;
		return sprintf('prince --http-timeout=3 --pdf-profile="PDF/A-3b" --pdf-author=%s', Utils::escapeshellarg($org_name));
	}

	/**
	 * Displays a PDF from a string, only works when PDF_COMMAND constant is set to "prince"
	 * @param  string $str HTML string
	 * @return void
	 */
	static public function streamPDF(string $str): void
	{
		if (!PDF_COMMAND) {
			throw new \LogicException('PDF generation is disabled');
		}

		$str = self::appendCookieToURLs($str);
		$str = preg_replace('!(<html.*?)class="!s', '$1class="pdf ', $str);

		if (PDF_COMMAND === 'auto') {
			// Try to see if there's a plugin
			$in = ['string' => $str];

			$signal = Plugins::fire('pdf.stream', true, $in);

			if ($signal && $signal->isStopped()) {
				return;
			}

			unset($signal, $in);
		}

		// Only Prince handles using STDIN and STDOUT
		if (PDF_COMMAND != 'prince') {
			$file = self::filePDF($str);
			readfile($file);
			unlink($file);
			return;
		}

		// 3 seconds is plenty enough to fetch resources, right?
		$cmd = self::getPrinceCommand() . ' -o - -';

		// Prince is fast, right? Fingers crossed
		self::exec($cmd, 10, $str, fn ($data) => print($data));

		if (PDF_USAGE_LOG) {
			file_put_contents(PDF_USAGE_LOG, date("Y-m-d H:i:s\n"), FILE_APPEND);
		}
	}

	/**
	 * Creates a PDF file from a HTML string
	 * @param  string $str HTML string
	 * @return string File path of the PDF file (temporary), you must delete or move it
	 */
	static public function filePDF(string $str): ?string
	{
		$cmd = PDF_COMMAND;

		if (!$cmd) {
			throw new \LogicException('PDF generation is disabled');
		}

		$source = sprintf('%s/print-%s.html', CACHE_ROOT, md5(random_bytes(16)));
		$target = str_replace('.html', '.pdf', $source);

		$str = self::appendCookieToURLs($str);
		$str = preg_replace('!(<html.*?)class="!s', '$1class="pdf ', $str);

		Utils::safe_mkdir(CACHE_ROOT, null, true);
		file_put_contents($source, $str);

		if ($cmd === 'auto') {
			// Try to see if there's a plugin
			$in = ['source' => $source, 'target' => $target];

			$signal = Plugins::fire('pdf.create', true, $in);

			if ($signal && $signal->isStopped()) {
				Utils::safe_unlink($source);
				return $target;
			}

			unset($in, $signal);

			// Try to find a local executable
			$list = ['prince', 'chromium', 'wkhtmltopdf', 'weasyprint'];
			$cmd = null;

			foreach ($list as $program) {
				if (self::quick_exec('which ' . $program, 1)) {
					$cmd = $program;
					break;
				}
			}

			// We still haven't found anything
			if (!$cmd) {
				throw new \LogicException('Aucun programme de création de PDF trouvé, merci d\'en installer un : https://fossil.kd2.org/paheko/wiki?name=Configuration');
			}
		}

		$timeout = 25;

		if ($cmd === 'prince') {
			$timeout = 10;
			$cmd = self::getPrinceCommand() . ' -o %2$s %1$s';
		}
		elseif ($cmd === 'chromium') {
			$cmd = 'chromium --headless --timeout=5000 --disable-gpu --run-all-compositor-stages-before-draw --print-to-pdf-no-header --print-to-pdf=%2$s %1$s';
		}
		elseif ($cmd === 'wkhtmltopdf') {
			$cmd = 'wkhtmltopdf -q --print-media-type --enable-local-file-access --disable-smart-shrinking --encoding "UTF-8" %s %s';
		}
		elseif ($cmd === 'weasyprint') {
			$timeout = 60;
			$cmd = 'weasyprint %1$s %2$s';
		}

		$cmd = sprintf($cmd, self::escapeshellarg($source), self::escapeshellarg($target));
		$cmd .= ' 2>&1';

		$output = '';

		try {
			$output = self::quick_exec($cmd, $timeout);
		}
		finally {
			Utils::safe_unlink($source);
		}

		if (!file_exists($target)) {
			throw new \RuntimeException('PDF command failed: ' . $output);
		}

		if (PDF_USAGE_LOG) {
			file_put_contents(PDF_USAGE_LOG, date("Y-m-d H:i:s\n"), FILE_APPEND);
		}

		return $target;
	}

	/**
	 * Integer to A-Z, AA-ZZ, AAA-ZZZ, etc.
	 * @see https://www.php.net/manual/fr/function.base-convert.php#94874
	 */
	static public function num2alpha(int $n): string {
		$r = '';
		for ($i = 1; $n >= 0 && $i < 10; $i++) {
			$r = chr(0x41 + intval($n % pow(26, $i) / pow(26, $i - 1))) . $r;
			$n -= pow(26, $i);
		}
		return $r;
	}

	static public function random_string(int $length): string
	{
		$bytes = ceil($length * 0.7);
		$str = base_convert(bin2hex(random_bytes($bytes)), 16, 36);
		return substr($str, 0, $length);
	}

	static public function uuid(): string
	{
		$uuid = bin2hex(random_bytes(16));

		return sprintf('%08s-%04s-4%03s-%04x-%012s',
			// 32 bits for "time_low"
			substr($uuid, 0, 8),
			// 16 bits for "time_mid"
			substr($uuid, 8, 4),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			substr($uuid, 13, 3),
			// 16 bits:
			// * 8 bits for "clk_seq_hi_res",
			// * 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			hexdec(substr($uuid, 16, 4)) & 0x3fff | 0x8000,
			// 48 bits for "node"
			substr($uuid, 20, 12)
		);
	}

	/**
	 * Hash de la version pour les éléments statiques (cache)
	 *
	 * On ne peut pas utiliser la version directement comme query string
	 * pour les éléments statiques (genre /admin/static/admin.css?v0.9.0)
	 * car cela dévoilerait la version de Paheko utilisée, posant un souci
	 * en cas de faille, on cache donc la version utilisée, chaque instance
	 * aura sa propre version
	 */
	static public function getVersionHash(): string
	{
		return substr(sha1(paheko_version() . paheko_manifest() . ROOT . SECRET_KEY), 0, 10);
	}

	/**
	 * Génération pagination à partir de la page courante ($current),
	 * du nombre d'items total ($total), et du nombre d'items par page ($bypage).
	 * $listLength représente la longueur d'items de la pagination à génerer
	 *
	 * @param int $current
	 * @param int $total
	 * @param int $bypage
	 * @param int $listLength
	 * @param bool $showLast Toggle l'affichage du dernier élément de la pagination
	 * @return array|null
	 */
	static public function getGenericPagination($current, $total, $bypage, $listLength = 11, $showLast = true)
	{
		if ($total <= $bypage)
			return null;

		$total = ceil($total / $bypage);

		if ($total < $current)
			return null;

		$length = ($listLength / 2);

		$begin = $current - ceil($length);
		if ($begin < 1)
		{
			$begin = 1;
		}

		$end = $begin + $listLength;
		if($end > $total)
		{
			$begin -= ($end - $total);
			$end = $total;
		}
		if ($begin < 1)
		{
			$begin = 1;
		}
		if($end==($total-1)) {
			$end = $total;
		}
		if($begin == 2) {
			$begin = 1;
		}
		$out = [];

		if ($current > 1) {
			$out[] = ['id' => $current - 1, 'label' =>  '« ' . 'Page précédente', 'class' => 'prev', 'accesskey' => 'a'];
		}

		if ($begin > 1) {
			$out[] = ['id' => 1, 'label' => '1 ...', 'class' => 'first'];
		}

		for ($i = $begin; $i <= $end; $i++)
		{
			$out[] = ['id' => $i, 'label' => $i, 'class' => ($i == $current) ? 'current' : ''];
		}

		if ($showLast && $end < $total) {
			$out[] = ['id' => $total, 'label' => '... ' . $total, 'class' => 'last'];
		}

		if ($current < $total) {
			$out[] = ['id' => $current + 1, 'label' => 'Page suivante' . ' »', 'class' => 'next', 'accesskey' => 'z'];
		}

		return $out;
	}

	static public function var_export($v)
	{
		if (is_array($v) || is_object($v)) {
			$out = '[';
			$keys = !array_key_exists(0, $v);

			foreach ($v as $k => $v) {
				if ($keys) {
					$out .= $k . ': ';
				}

				$out .= self::var_export($v) . ', ';
			}

			$out = (strlen($out) > 1 ? substr($out, 0, -2) : $out) . ']';
		}
		elseif (is_bool($v)) {
			$out = $v ? 'true' : 'false';
		}
		elseif (is_null($v)) {
			$out = 'null';
		}
		elseif (is_string($v)) {
			$out = '"' . strtr($v, ['"' => '\\"', "\n" => "\\n", "\r" => "\\r"]) . '"';
		}
		else {
			$out = $v;
		}
		return $out;
	}

	static public function parse_ini_file(string $path, bool $sections = false)
	{
		return self::parse_ini_string(file_get_contents($path), $sections);
	}

	/**
	 * Safe alternative to parse_ini_string without constant/variable expansion
	 * but still type values, like INI_SCANNER_TYPED
	 */
	static public function parse_ini_string(string $ini, bool $sections = false)
	{
		try {
			$ini = parse_ini_string($ini, $sections, \INI_SCANNER_RAW);
		}
		catch (\Throwable $e) {
			throw new \RuntimeException($e->getMessage(), 0, $e);
		}

		return self::_resolve_ini_types($ini);
	}

	static protected function _resolve_ini_types(array $ini)
	{
		foreach ($ini as &$value) {
			if (is_array($value)) {
				$value = self::_resolve_ini_types($value);
			}
			elseif ($value === 'FALSE' || $value === 'false' || $value === 'off' || $value === 'no' || $value === 'none') {
				$value = false;
			}
			elseif ($value === 'TRUE' || $value === 'true' || $value === 'on' || $value === 'yes') {
				$value = true;
			}
			elseif ($value === 'NULL' || $value === 'null') {
				$value = null;
			}
			elseif (ctype_digit($value)) {
				$value = (int)$value;
			}
			else {
				$value = str_replace('\n', "\n", $value);
			}
		}

		unset($value);

		return $ini;
	}

	static public function export_value($value, int $level = 0): string
	{
		$out = '';

		if (is_object($value) || is_array($value)) {
			foreach ($value as $key => $subvalue) {
				if (empty($subvalue)) {
					continue;
				}
				$out .= str_repeat(' ', $level) . $key . ': ' . self::export_value($subvalue, $level+1) . "\n";
			}
		}
		else {
			$out .= (string)$value;
		}

		return rtrim($out);
	}

	static public function is_json($value): bool
	{
		if (is_string($value) && substr($value, 0, 1) === '{' && substr($value, -1) === '}') {
			return true;
		}

		return false;
	}

	static public function showProfiler(): void
	{
		if (!defined('Paheko\PROFILER_START_TIME')) {
			return;
		}

		$is_html = false;

		foreach (headers_list() as $header) {
			if (false !== stripos($header, 'Content-Type: text/html')) {
				$is_html = true;
				break;
			}
		}

		if (!$is_html) {
			return;
		}

		$now = microtime(true);
		$start_time = round((PROFILER_START_TIME - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
		$time = round(($now - PROFILER_START_TIME) * 1000);
		$mem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

		$db = DB::getInstance();
		$db_log = $db->getLog();
		$db_time = 0;
		$has_slow_queries = false;

		foreach ($db_log as &$item) {
			$db_time += $item['duration'];

			$item['plan'] = '';

			if (preg_match('!^\s*SELECT\s+!i', $item['sql'])) {
				try {
					foreach ($db->get('EXPLAIN QUERY PLAN ' . $item['sql']) as $e) {
						$item['plan'] .= $e->detail . "\n";
					}
				}
				catch (DB_Exception $e) {
					$item['plan'] = 'Error: ' . $e->getMessage();
				}
			}

			if ($item['duration'] >= 4000) {
				$has_slow_queries = true;
			}
		}

		unset($item);

		printf('<style type="text/css">
			body { padding-bottom: 3em; }
			#__profiler * { margin: 0; padding: 0; }
			#__profiler { position: fixed; bottom: 0; left: 0; right: 0; z-index: 30000; }
			#__profiler header { display: flex; cursor: pointer; background: #000; color: #fff; height: 2em; }
			#__profiler header:hover { background: #600; }
			#__profiler span { display: flex; align-items: center; padding: .2em .7em; font-size: 1em; border-right: 1px solid #666; }
			#__profiler span i { display: block; font-size: 1.8em; font-style: normal; margin-right: .5em; line-height: 1em; }
			#__profiler.log { top: 0; }
			#__profiler table { display: none; }
			#__profiler.log table { display: block; background: #fff; width: 100%%; height: calc(100%% - 2em); color: #000; border-collapse: collapse; overflow: auto; }
			#__profiler.log td, #__profiler.log th { padding: .5em; vertical-align: top; font-weight: normal; text-align: left; }
			#__profiler.log th { white-space: pre-wrap; font-family: monospace; }
			#__profiler.log tbody tr:nth-child(even) { background: #eee; }
			#__profiler.log thead td { font-weight: bold; background: #ddd; }
			#__profiler span.slow { background: darkred; }
			#__profiler.log tbody tr.slow { background: #fcc; }
			</style>
			<div id="__profiler">
				<header onclick="this.parentNode.classList.toggle(\'log\');">
					<span title="Time before start of Paheko"><i>⇥</i>%d ms</span>
					<span title="Time taken by Paheko" class="%s"><i>🕑</i> %s ms</span>
					<span title="RAM usage"><i>🍔</i>%s MiB</span>
					<span title="SQL" class="%s"><i>⛁</i> %d in %d ms</span>
				</header>
				<table>
					<thead><tr><td>Time</td><td>SQL</td><td>EXPLAIN</td><td></td></tr></thead>
					<tbody>',
			$start_time,
			$time > 150 ? 'slow' : '',
			$time,
			$mem,
			$has_slow_queries ? 'slow' : '',
			count($db_log),
			round($db_time / 1000)
		);

		$sql_url = self::getLocalURL('!config/advanced/sql.php') . '?query=';

		foreach ($db_log as $item) {
			$plan = htmlspecialchars($item['plan']);
			$plan = preg_replace('/\bSCAN\b(?:(?!\s+USING).)*$/m', '<b style="color:red">$0</b>', $plan);

			printf('<tr class="%s"><td>%s&nbsp;ms</td><th>%s</th><th>%s</th><td><a href="%s">[Replay]</a></td></tr>',
				$item['duration'] >= 4000 ? 'slow' : '',
				round($item['duration'] / 1000, 2),
				htmlspecialchars($item['sql']),
				$plan,
				$sql_url . rawurlencode($item['sql'])
			);
		}

		echo '</tbody></table></div>';

		@ob_end_flush();
		@flush();
	}
}
