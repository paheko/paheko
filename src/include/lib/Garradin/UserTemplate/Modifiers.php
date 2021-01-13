<?php

namespace Garradin\UserTemplate;

use Garradin\Utils;

class Modifiers
{
	const DEFAULTS = [
		'relative_date' => [Utils::class, 'relative_date'],
		'truncate' => [self::class, 'truncate'],
	];

	/**
	 * UTF-8 aware intelligent substr
	 * @param  string  $str         UTF-8 string
	 * @param  integer $length      Maximum string length
	 * @param  string  $placeholder Placeholder text to append at the string if it has been cut
	 * @param  boolean $strict_cut  If true then will cut in the middle of words
	 * @return string 				String cut to $length or shorter
	 * @example |truncate:10:" (click to read more)":true
	 */
	static public function truncate($str, $length = 80, $placeholder = '…', $strict_cut = false)
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
		}

		return trim($str) . $placeholder;
	}
}