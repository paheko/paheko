<?php

namespace Garradin\UserTemplate;

use Garradin\Config;
use Garradin\Utils;

/**
 * Common modifiers used by Template (Smartyer) and UserTemplate
 */
class CommonModifiers
{
	const PHP_MODIFIERS_LIST = [
		'strtolower',
		'strtoupper',
		'ucfirst',
		'ucwords',
		'strtotime',
		'htmlentities',
		'htmlspecialchars',
		'trim',
		'ltrim',
		'rtrim',
		'lcfirst',
		'md5',
		'sha1',
		'metaphone',
		'nl2br',
		'soundex',
		'str_split',
		'str_word_count',
		'strrev',
		'strlen',
		'strpos',
		'strrpos',
		'wordwrap',
		'strip_tags',
		'strlen',
		'boolval',
		'intval',
		'floatval',
		'substr',
		'abs',
		'base64_encode'
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
		'money',
		'money_raw',
		'money_currency',
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
	];

	/**
	 * See also money/money_currency in UserTemplate (overriden)
	 */
	static public function money($number, bool $hide_empty = true, bool $force_sign = false): string
	{
		if ($hide_empty && !$number) {
			return '';
		}

		$sign = ($force_sign && $number > 0) ? '+' : '';

		return sprintf('<b class="money">%s</b>', $sign . Utils::money_format($number, ',', '&nbsp;', $hide_empty));
	}

	static public function money_raw($number, bool $hide_empty = true): string
	{
		return Utils::money_format($number, ',', '', $hide_empty);
	}

	static public function money_currency($number, bool $hide_empty = true): string
	{
		$out = self::money($number, $hide_empty);

		if ($out !== '') {
			$out .= '&nbsp;' . Config::getInstance()->get('currency');
		}

		return $out;
	}

	static public function date_long($ts, bool $with_hour = false): ?string
	{
		return Utils::strftime_fr($ts, '%A %e %B %Y' . ($with_hour ? ' à %Hh%M' : ''));
	}

	static public function date_short($ts, bool $with_hour = false): ?string
	{
		return Utils::date_fr($ts, 'd/m/Y' . ($with_hour ? ' à H\hi' : ''));
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
		elseif ($date->getTimestamp() > time() - 3600*24*7) {
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
		$str = preg_replace('/[\h]*([?!:»])(\s+|$)/u', '&nbsp;\\1\\2', $str);
		$str = preg_replace('/(^|\s+)([«])[\h]*/u', '\\1\\2&nbsp;', $str);
		return $str;
	}

	static public function css_hex_to_rgb($str): ?string {
		$hex = sscanf((string)$str, '#%02x%02x%02x');

		if (empty($hex)) {
			return null;
		}

		return implode(', ', $hex);
	}
}
