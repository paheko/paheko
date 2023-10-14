<?php

namespace Paheko;

/*
 * Gestion des erreurs et exceptions
 */

class UserException extends \LogicException
{
	protected $details = null;
	protected ?string $html_message = null;

	public function setMessage(string $message) {
		$this->message = $message;
	}

	public function getHTMLMessage(): ?string {
		return $this->html_message;
	}

	public function setHTMLMessage(string $html): void {
		$this->html_message = $html;
	}

	public function setDetails($details) {
		$this->details = $details;
	}

	public function getDetails() {
		return $this->details;
	}

	public function hasDetails(): bool {
		return $this->details !== null;
	}

	public function getDetailsHTML() {
		if (func_num_args() == 1) {
			$details = func_get_arg(0);
		}
		else {
			$details = $this->details;
		}

		if (null === $details) {
			return '<em>(nul)</em>';
		}

		if ($details instanceof \DateTimeInterface) {
			return $details->format('d/m/Y');
		}

		if (!is_array($details)) {
			return nl2br(htmlspecialchars($details));
		}

		$out = '<table>';

		foreach ($details as $key => $value) {
			$out .= sprintf('<tr><th>%s</th><td>%s</td></tr>', htmlspecialchars($key), $this->getDetailsHTML($value));
		}

		$out .= '</table>';

		return $out;
	}
}
