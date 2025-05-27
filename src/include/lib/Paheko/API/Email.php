<?php

namespace Paheko\API;

use Paheko\Email\Queue;
use Paheko\Entities\Email\Message;
use Paheko\APIException;

use const Paheko\DISABLE_EMAIL_SENDING_API;

trait Web
{
	protected function email(string $uri): ?array
	{
		$fn = strtok($uri, '/');
		$param = strtok('');

		if ($fn === 'queue') {
			$this->requireMethod('POST');
			$this->requireAccess(Session::ACCESS_ADMIN);

			if (DISABLE_EMAIL_SENDING_API) {
				throw new APIException('The email sending API is disabled', 403);
			}

			if (empty($this->params['context'])) {
				throw new APIException('Missing "context" parameter', 400);
			}

			if (empty($this->params['to']) || !is_array($this->params['to'])) {
				throw new APIException('Missing "to" parameter', 400);
			}

			$contexts = [
				'bulk'         => Message::CONTEXT_BULK,
				'notification' => Message::CONTEXT_NOTIFICATION,
				'private'      => Message::CONTEXT_PRIVATE,
			];

			if (!array_key_exists($this->params['context'], $contexts)) {
				throw new APIException('Unknown "context" parameter: ' . $this->params['context'], 400);
			}

			$context = $contexts[$this->params['context']];

			if ((isset($this->params['body']) || isset($this->params['html_body']))
				&& (isset($this->params['template']) || isset($this->params['html_template']))) {
				throw new APIException('Cannot specify both a "body" or "html_body" parameter with "template" or "html_template"', 400);
			}

			$msg = Queue::createMessage(
				$context,
				$this->params['subject'] ?? null,
				$this->params['body'] ?? null,
				$this->params['html_body'] ?? null
			);

			$html_template = $template = null;
			$markdown = $this->hasParamTrue('markdown');
			$wrap = $this->hasParamTrue('wrap') || !$this->hasParam('wrap');

			if (!empty($this->params['html_template']) && is_string($this->params['html_template'])) {
				$html_template = UserTemplate::createFromUserString($this->params['html_template']);
			}

			if (!empty($this->params['template']) && is_string($this->params['template'])) {
				$template = UserTemplate::createFromUserString($this->params['template']);
			}

			foreach ($this->params['to'] as $idx => $r) {
				if (is_numeric($idx) && is_string($r)) {
					$data = null;
					$address = $r;
				}
				else {
					$data = (array) $r;
					$address = (string) $idx;
				}

				if ($html_template) {
					$msg->setHTMLBodyFromUserTemplate($html_template, $data);
				}

				if ($template) {
					$msg->setBodyFromUserTemplate($template, $data, $markdown);
				}
				elseif ($markdown) {
					$msg->markdownToHTML();
				}

				if ($wrap) {
					$msg->wrapHTML();
				}

				$msg->queue();
			}
		}
		else {
			throw new APIException('Unknown email route', 404);
		}
	}
}
