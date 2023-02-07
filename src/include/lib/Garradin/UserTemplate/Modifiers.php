<?php

namespace Garradin\UserTemplate;

use Garradin\Utils;
use Garradin\UserException;

use Garradin\Entities\Users\Email;

use KD2\SMTP;

class Modifiers
{
	const PHP_MODIFIERS_LIST = [
		'strtolower',
		'strtoupper',
		'ucfirst',
		'ucwords',
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
		'wordwrap',
		'strip_tags',
		'strlen',
		'abs',
	];

	const MODIFIERS_LIST = [
		'truncate',
		'excerpt',
		'protect_contact',
		'atom_date',
		'xml_escape',
		'replace',
		'regexp_replace',
		'regexp_match',
		'match',
		'remove_leading_number',
		'get_leading_number',
		'check_email',
		'urlencode',
	];

	const LEADING_NUMBER_REGEXP = '/^([\d.]+)\s*[.\)]\s*/';

	static public function replace($str, $find, $replace): string
	{
		return str_replace($find, $replace, $str);
	}

	static public function regexp_replace($str, $pattern, $replace)
	{
		return preg_replace($pattern, $replace, $str);
	}

	static public function regexp_match($str, $pattern)
	{
		return (int) preg_match($pattern, $str);
	}

	static public function match($str, $pattern)
	{
		return (int) (stripos($str, $pattern) !== false);
	}

	static public function check_email($str)
	{
		if (!trim((string)$str)) {
			return false;
		}

		try {
			Email::validateAddress((string)$str);
		}
		catch (UserException $e) {
			return false;
		}

		return true;
	}

	/**
	 * UTF-8 aware intelligent substr
	 * @param  string  $str         UTF-8 string
	 * @param  integer $length      Maximum string length
	 * @param  string  $placeholder Placeholder text to append at the string if it has been cut
	 * @param  boolean $strict_cut  If true then will cut in the middle of words
	 * @return string 				String cut to $length or shorter
	 * @example |truncate:10:" (click to read more)":true
	 */
	static public function truncate($str, $length = 80, $placeholder = '…', $strict_cut = false): string
	{
		// Don't try to use unicode if the string is not valid UTF-8
		$u = preg_match('//u', $str) ? 'u' : '';

		// Shorter than $length + 1
		if (!preg_match('/^.{' . ((int)$length + 1) . '}/s' . $u, $str))
		{
			return $str;
		}

		// Cut at 80 characters
		$str = preg_replace('/^(.{0,' . (int)$length . '}).*$/s' . $u, '$1', $str);

		if (!$strict_cut)
		{
			$cut = preg_replace('/[^\s.,:;!?]*?$/s' . $u, '', $str);

			if (trim($cut) == '') {
				$cut = $str;
			}

			$str = $cut;
		}

		return trim($str) . $placeholder;
	}

	static public function excerpt($str, $length = 600): string
	{
		$str = strip_tags($str);
		$str = self::truncate($str, $length);
		$str = preg_replace("/\n{2,}/", '</p><p>', $str);
		return '<p>' . $str . '</p>';
	}

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

	static public function atom_date($date)
	{
		return Utils::date_fr($date, DATE_ATOM);
	}

	static public function xml_escape($str)
	{
		return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES);
	}

	static public function remove_leading_number($str): string
	{
		return preg_replace(self::LEADING_NUMBER_REGEXP, '', trim($str));
	}

	static public function get_leading_number($str): ?string
	{
		$match = preg_match(self::LEADING_NUMBER_REGEXP, $str);
		return $match[1] ?? null;
	}

	static public function urlencode($str): string
	{
		return rawurlencode($str ?? '');
	}
}
