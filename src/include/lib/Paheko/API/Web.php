<?php

namespace Paheko\API;

use Paheko\Web\Web as PWeb;
use Paheko\Files\Files;
use Paheko\Entities\Web\Page;
use Paheko\Entities\Files\File;
use Paheko\Users\Session;
use Paheko\APIException;

trait Web
{
	protected function web(string $uri): ?array
	{
		$fn = strtok($uri, '/');
		$param = strtok('');

		if (!$fn) {
			$this->requireMethod('GET');
			$list = PWeb::getAllList();
			$list->setPageSize(null);
			return $this->export($list->iterate());
		}

		if (substr($fn, -5) === '.html') {
			$fn = substr($fn, 0, -5);
			$param = 'html';
		}

		$page = PWeb::getByURI($fn);

		if (!$page && !($this->method === 'POST' && !$param)) {
			throw new APIException('Page not found', 404);
		}

		if (!$param) {
			if ($this->method === 'GET') {
				$out = $page->asArray(true);

				if ($this->hasParamTrue('html')) {
					$out['html'] = $page->render();
				}

				return $out;
			}
			elseif ($this->method === 'DELETE') {
				$this->requireAccess(Session::ACCESS_ADMIN);
				$page->delete();
				return self::SUCCESS;
			}
			elseif ($this->method === 'PUT') {
				$this->requireAccess(Session::ACCESS_WRITE);
				$page->set('content', $this->getInput());
				$page->saveNewVersion();
				return $page->asArray(true);
			}
			elseif ($this->method === 'POST') {
				$this->requireAccess(Session::ACCESS_WRITE);

				if (!$page) {
					$page = new Page;
					$this->params['uri'] = $fn;
				}

				$page->importForm($this->params);
				$page->saveNewVersion();
				return $page->asArray(true);
			}
			else {
				throw new APIException('Invalid request method', 405);
			}
		}
		elseif ($param === 'html') {
			$this->requireMethod('GET');

			if (!$this->is_http_client) {
				return $this->export($page->render());
			}

			http_response_code(200);
			header('Content-Type: text/html; charset=utf-8', true);
			echo $page->render();
			return null;
		}
		elseif ($param === 'children') {
			$this->requireMethod('GET');
			$list = PWeb::getAllList($page->id());
			$list->setPageSize(null);
			return $this->export($list->iterate());
		}
		elseif ($param === 'attachments') {
			$this->requireMethod('GET');
			$out = [];

			foreach ($page->listAttachments() as $file) {
				$a = $file->asArray(true);
				$a['url'] = $file->url();
				$out[] = $a;
			}

			return $out;
		}
		else {
			try {
				File::validateFileName($param);
			}
			catch (ValidationException $e) {
				throw new APIException($e->getMessage(), 400, $e);
			}

			$path = File::CONTEXT_WEB . '/' . $page->uri . '/' . $param;

			if ($this->method === 'PUT') {
				$this->requireAccess(Session::ACCESS_WRITE);
				Files::createFromPointer($path, $this->getFilePointer(), null);
				$this->closeFilePointer();
				return self::SUCCESS;
			}
			else {
				$attachment = Files::get($path);

				if (!$attachment) {
					throw new APIException('Attachment not found', 404);
				}

				if ($this->method === 'GET') {
					$attachment->serve();
					return null;
				}
				elseif ($this->method === 'DELETE') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$attachment->delete();
					return self::SUCCESS;
				}
				else {
					throw new APIException('Invalid method', 405);
				}
			}
		}
	}
}
