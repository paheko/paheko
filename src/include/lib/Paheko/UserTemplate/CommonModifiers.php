<?php

namespace Paheko\UserTemplate;

use Paheko\Config;
use Paheko\Utils;

use Paheko\Web\Render\Markdown;

/**
 * Common modifiers used by Template (Smartyer) and UserTemplate
 */
class CommonModifiers
{
	const PHP_MODIFIERS_LIST = [
		'strtotime',
		'htmlentities',
		'htmlspecialchars',
		'trim',
		'ltrim',
		'rtrim',
		'md5',
		'sha1',
		'nl2br',
		'strlen',
		'strpos',
		'strrpos',
		'wordwrap',
		'strip_tags',
		'strlen',
		'boolval',
		'intval',
		'floatval',
		'strval',
		'substr',
		'http_build_query',
		'str_getcsv',
	];

	/**
	 * Used for PHP modifiers
	 */
	static public function __callStatic(string $name, array $arguments)
	{
		if (!in_array($name, self::PHP_MODIFIERS_LIST)) {
			throw new \Exception('Invalid method: ' . $name);
		}

		// That change sucks PHP :(
		// https://php.watch/versions/8.1/internal-func-non-nullable-null-deprecation
		if (PHP_VERSION_ID >= 80100) {
			foreach ($arguments as &$arg) {
				if (null === $arg) {
					$arg = '';
				}
			}

			unset($arg);
		}

		return call_user_func_array($name, $arguments);
	}

	const MODIFIERS_LIST = [
		'protect_contact',
		'markdown',
		'money',
		'money_raw',
		'money_currency',
		'money_html',
		'money_currency_html',
		'relative_date',
		'relative_date_short',
		'date_short',
		'date_long',
		'date_hour',
		'date',
		'strftime',
		'size_in_bytes' => [Utils::class, 'format_bytes'],
		'typo',
		'css_hex_to_rgb',
		'css_hex_extract_hsv',
		'toupper',
		'tolower',
		'ucwords',
		'ucfirst',
		'lcfirst',
		'abs',
	];

	static public function protect_contact(?string $contact, ?string $type = null): string
	{
		if (!trim($contact))
			return '';

		if ($type == 'mail' || strpos($contact, '@')) {
			$user = strtok($contact, '@');
			$domain = strtok('.');
			$ext = strtok(false);

			return sprintf('<a href="#error" class="protected-contact" data-a="%s" data-b="%s" data-c="%s"
				onclick="if (this.href.match(/#error/)) this.href = [\'mail\', \'to:\', this.dataset.a, \'@\', this.dataset.b, \'.\' + this.dataset.c].join(\'\');"></a>',
				htmlspecialchars($user), htmlspecialchars($domain), htmlspecialchars($ext));
		}
		else {
			$label = preg_replace_callback('/[a-zA-Z0-9@]/', fn ($match)  => '&#' . ord($match[0]) . ';', htmlspecialchars($contact));
			$url = htmlspecialchars($type ? $type . ':' : '') . $label;
			return sprintf('<a href="%s">%s</a>', $url, $label);
		}
	}

	static public function markdown($str): string
	{
		$md = new Markdown(null, null);
		return $md->render($str);
	}

	static public function money($number, bool $hide_empty = true, bool $force_sign = false, bool $html = false): string
	{
		if ($hide_empty && !$number) {
			return '';
		}

		$sign = ($force_sign && $number > 0) ? '+' : '';

		$out = $sign . Utils::money_format($number, ',', $html ? '&nbsp;' : ' ', $hide_empty);

		if ($html) {
			$out = sprintf('<span class="money">%s</span>', $out);
		}

		return $out;
	}

	static public function money_raw($number, bool $hide_empty = true): string
	{
		return Utils::money_format($number, ',', '', $hide_empty);
	}

	static public function money_currency($number, bool $hide_empty = true, bool $force_sign = false, bool $html = false): string
	{
		$out = self::money($number, $hide_empty, $force_sign, $html);

		if ($out !== '') {
			$out .= ($html ? '&nbsp;' : ' ') . Config::getInstance()->get('currency');
		}

		return $out;
	}

	static public function money_html($number, bool $hide_empty = true, bool $force_sign = false): string
	{
		return self::money($number, $hide_empty, $force_sign, true);
	}

	static public function money_currency_html($number, bool $hide_empty = true, bool $force_sign = false): string
	{
		return '<nobr>' . self::money_currency($number, $hide_empty, $force_sign, true) . '</nobr>';
	}

	static public function date_long($ts, bool $with_hour = false): ?string
	{
		return Utils::strftime_fr($ts, '%A %e %B %Y' . ($with_hour ? ' à %Hh%M' : ''));
	}

	static public function date_short($ts, bool $with_hour = false): ?string
	{
		return Utils::shortDate($ts, $with_hour);
	}

	static public function date_hour($ts, bool $minutes_only_if_required = false): ?string
	{
		$ts = Utils::get_datetime($ts);

		if (null === $ts) {
			return null;
		}

		if ($minutes_only_if_required && $ts->format('i') == '00') {
			return $ts->format('H\h');
		}
		else {
			return $ts->format('H\hi');
		}
	}

	static public function strftime($ts, string $format, string $locale = 'fr'): ?string
	{
		if ($locale == 'fr') {
			return Utils::strftime_fr($ts, $format);
		}

		$ts = Utils::get_datetime($ts);

		if (!$ts) {
			return $ts;
		}

		return @strftime($format, $ts->getTimestamp());
	}

	static public function date($ts, string $format = null, string $locale = 'fr'): ?string
	{
		if (null === $format) {
			$format = 'd/m/Y à H:i';
		}
		elseif (preg_match('/^DATE_[\w\d]+$/', $format)) {
			$format = constant('DateTime::' . $format);
		}

		if ($locale == 'fr') {
			return Utils::date_fr($ts, $format);
		}

		$ts = Utils::get_datetime($ts);
		return date($format, $ts);
	}

	static public function relative_date($ts, bool $with_hour = false): string
	{
		$day = null;

		if (null === $ts) {
			return '';
		}

		$date = Utils::get_datetime($ts);

		if ($date->format('Ymd') == date('Ymd'))
		{
			$day = 'aujourd\'hui';
		}
		elseif ($date->format('Ymd') == date('Ymd', strtotime('yesterday')))
		{
			$day = 'hier';
		}
		elseif ($date->format('Ymd') == date('Ymd', strtotime('tomorrow')))
		{
			$day = 'demain';
		}
		elseif ($date->getTimestamp() > time() - 3600*24*7 && $date->getTimestamp() < time()) {
			$day = sprintf('il y a %d jours', round((time() - $date->getTimestamp()) / (3600*24)));
		}
		elseif ($date->format('Y') == date('Y'))
		{
			$day = strtolower(Utils::strftime_fr($date, '%A %e %B'));
		}
		else
		{
			$day = strtolower(Utils::strftime_fr($date, '%e %B %Y'));
		}

		if ($with_hour)
		{
			$hour = $date->format('H\hi');
			return sprintf('%s, %s', $day, $hour);
		}

		return $day;
	}

	static public function relative_date_short($ts, bool $with_hour = false): string
	{
		$day = null;

		if (null === $ts) {
			return '';
		}

		$date = Utils::get_datetime($ts);

		if ($date->format('Ymd') == date('Ymd'))
		{
			$day = 'aujourd\'hui';
		}
		elseif ($date->format('Ymd') == date('Ymd', strtotime('yesterday')))
		{
			$day = 'hier';
		}
		elseif ($date->format('Ymd') == date('Ymd', strtotime('tomorrow')))
		{
			$day = 'demain';
		}
		elseif ($date->getTimestamp() > time() - 3600*24*7) {
			$day = sprintf('il y a %d jours', round((time() - $date->getTimestamp()) / (3600*24)));
		}
		elseif ($date->format('Y') == date('Y'))
		{
			$day = strtolower(Utils::strftime_fr($date, '%e %B'));
		}
		else
		{
			$day = strtolower(Utils::strftime_fr($date, '%d/%m/%Y'));
		}

		if ($with_hour)
		{
			$hour = $date->format('H\hi');
			return sprintf('%s, %s', $day, $hour);
		}

		return $day;
	}

	static public function typo($str, $locale = 'fr')
	{
		$str = preg_replace('/[\h]*([?!:»;])(?=\s|$)/us', "\xc2\xa0\\1", $str);
		$str = preg_replace('/(?<=^|\s)([«])[\h]*/u', "\\1\xc2\xa0", $str);
		return $str;
	}

	static public function css_hex_to_rgb($str): ?string {
		$hex = sscanf((string)$str, '#%02x%02x%02x');

		if (empty($hex)) {
			return null;
		}

		return implode(', ', $hex);
	}

	static public function css_hex_extract_hsv($str): array {
		list($h, $s, $v) = Utils::rgbToHsv($str);
		$h = (int)$h;
		$s = floor(100 * $s);
		$v = floor(100 * $v);
		return compact('h', 's', 'v');
	}

	static public function toupper($str): string
	{
		return function_exists('mb_strtoupper') ? mb_strtoupper($str) : strtoupper($str);
	}

	static public function tolower($str): string
	{
		return function_exists('mb_strtolower') ? mb_strtolower($str) : strtolower($str);
	}

	static public function ucwords($str): string
	{
		return function_exists('mb_convert_case') ? mb_convert_case($str, \MB_CASE_TITLE) : ucwords($str);
	}

	static public function ucfirst($str): string
	{
		return function_exists('mb_strtoupper') ? mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1) : ucfirst($str);
	}

	static public function lcfirst($str): string
	{
		return function_exists('mb_strtolower') ? mb_strtolower(mb_substr($str, 0, 1)) . mb_substr($str, 1) : ucfirst($str);
	}

	static public function abs($in)
	{
		if (false !== strpos((string)$in, '.')) {
			$in = (float) $in;
		}
		else {
			$in = (int) $in;
		}

		return abs($in);
	}
}
