<?php

namespace Paheko\UserTemplate;

use Paheko\Config;
use Paheko\TemplateException;
use Paheko\Utils;
use Paheko\UserException;

use Paheko\Web\Render\Markdown;

use KD2\Translate;

/**
 * Common modifiers used by Template (Smartyer) and UserTemplate
 */
class CommonModifiers
{
	/**
	 * List of accepted PHP functions as modifiers
	 *
	 * Key is name of function, value is list of accepted parameters
	 * Each parameter is a list of parameters types, see Brindille for details.
	 */
	const PHP_MODIFIERS_LIST = [
		'strtotime' => ['scalar+', '?int'],
		'htmlentities' => ['string+', 'int', '?string', 'bool'],
		'htmlspecialchars' => ['string+', 'int', '?string', 'bool'],
		'trim' => ['string+', 'string+'],
		'ltrim' => ['string+', 'string+'],
		'rtrim' => ['string+', 'string+'],
		'md5' => ['string+', 'bool'],
		'sha1' => ['string+', 'bool'],
		'nl2br' => ['string+', 'bool'],
		'strlen' => ['string+'],
		'strpos' => ['string+', 'string+', 'int'],
		'strrpos' => ['string+', 'string+', 'int'],
		'wordwrap' => ['string+', 'int', 'string+', 'bool'],
		'boolval' => [null],
		'intval' => [null],
		'floatval' => [null],
		'strval' => [null],
		'substr' => ['string+', 'int', 'int'],
		'http_build_query' => ['array', 'string', '?string', 'int'],
	];

	const MODIFIERS_LIST = [
		'protect_contact' => ['string+', '?string'],
		'markdown' => ['?string+'],
		'money' => ['?numeric', 'bool', 'bool', 'bool'],
		'money_raw' => ['?numeric', 'bool'],
		'money_currency' => ['?numeric', 'bool', 'bool', 'bool'],
		'money_html' => ['?numeric', 'bool', 'bool'],
		'money_currency_html' => ['?numeric', 'bool', 'bool'],
		'relative_date' => ['?DateTimeInterface|string|int', 'bool'],
		'relative_date_short' => ['?DateTimeInterface|string|int', 'bool'],
		'date_short' => ['?DateTimeInterface|string|int', 'bool'],
		'date_long' => ['?DateTimeInterface|string|int', 'bool'],
		'date_hour' => ['?DateTimeInterface|string|int', 'bool'],
		'date' => ['?DateTimeInterface|string|int', 'string', 'string'],
		'size_in_bytes' => ['callback' => [Utils::class, 'format_bytes'], 'types' => ['?numeric']],
		'weight' => ['callback' => [Utils::class, 'format_weight'], 'types' => ['?numeric', 'bool', 'bool']],
		'weightval' => ['callback' => [Utils::class, 'weightToInteger'], 'types' => ['?numeric']],
		'typo' => ['string+'],
		'css_hex_to_rgb' => ['string'],
		'css_hex_extract_hsv' => ['string'],
		'abs' => ['?numeric'],
		'format_phone_number' => ['string+'],
		'get_country_name' => ['callback' => [Utils::class, 'getCountryName']],
		'str_getcsv' => ['string+', 'string', 'string', 'string'],
	];

	static public function protect_contact(?string $contact, ?string $type = null): string
	{
		if (!trim($contact))
			return '';

		if ($type == 'mail' || strpos($contact, '@')) {
			$user = strtok($contact, '@');
			$domain = strtok('.');
			$ext = strtok('');

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
		$date = Utils::parseDateTime($ts);

		if (null === $date) {
			return null;
		}

		if ($minutes_only_if_required && $date->format('i') == '00') {
			return $date->format('H\h');
		}
		else {
			return $date->format('H\hi');
		}
	}

	static public function strftime($ts, string $format, string $locale = 'fr'): ?string
	{
		if ($locale == 'fr') {
			return Utils::strftime_fr($ts, $format);
		}

		$ts = Utils::parseDateTime($ts);

		if (!$ts) {
			return $ts;
		}

		return Translate::strftime($format, $ts);
	}

	static public function date($ts, ?string $format = null, string $locale = 'fr'): ?string
	{
		if (null === $format) {
			$format = 'd/m/Y à H:i';
		}
		elseif (preg_match('/^DATE_[A-Z0-9_]+$/', $format)) {
			$format = str_replace('DATE_', '', $format);

			if (!defined('DateTime::' . $format)) {
				throw new TemplateException('Invalid format: ' . $format);
			}

			$format = constant('DateTime::' . $format);
		}

		if ($locale == 'fr') {
			return Utils::date_fr($ts, $format);
		}

		$ts = Utils::parseDateTime($ts);
		return date($format, $ts);
	}

	static public function relative_date($ts, bool $with_hour = false): string
	{
		if (null === $ts) {
			return '';
		}

		$date = Utils::parseDateTime($ts);

		if (!$date) {
			return '';
		}

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
		if (null === $ts) {
			return '';
		}

		$date = Utils::parseDateTime($ts);

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
		if (empty($str)) {
			return $str;
		}

		$str = preg_replace('/(?:\h|(?!&\w))([?!:»;€])(?=\s|$)/us', "\xc2\xa0\\1", $str);
		$str = preg_replace('/(\d) +(\d{3})/', "\\1\xc2\xa0\\2", $str);
		$str = preg_replace('/(?<=^|\s)([«])[\h]*/u', "\\1\xc2\xa0", (string)$str);
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

	static public function format_phone_number($n)
	{
		if (empty($n)) {
			return '';
		}

		$country = Config::getInstance()->get('country');

		if ($country !== 'FR') {
			return $n;
		}

		if (strlen($n) === 10 && $n[0] === '0') {
			$n = preg_replace('!(\d{2})!', '\\1 ', $n);
		}

		return $n;
	}

	static public function str_getcsv(string $string, string $separator = ',', string $enclosure = '"', string $escape = '\\'): array
	{
		return str_getcsv($string, $separator, $enclosure, $escape);
	}
}
