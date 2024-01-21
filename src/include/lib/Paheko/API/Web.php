<?php

namespace Paheko\API;

use Paheko\Web\Web as PWeb;

trait Web
{
	protected function web(string $uri): ?array
	{
		if ($this->method != 'GET') {
			throw new APIException('Wrong request method', 400);
		}

		$fn = strtok($uri, '/');
		$param = strtok('');

		if (!$fn) {
			$this->requireMethod('GET');
			$list = PWeb::getAllList();
			$list->setPageSize(null);
			return iterator_to_array($list->iterate());
			return [
				'categories' => array_map(fn($p) => $p->asArray(true), PWeb::listCategories($param)),
				'pages' => array_map(fn($p) => $p->asArray(true), PWeb::listPages($param)),
			];
		}

		if (substr($fn, 0, -5) === '.html') {
			$fn = substr($fn, 0, -5);
			$param = 'html';
		}

		$page = PWeb::getByURI($fn);

		if (!$page) {
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
				$page->set('content', self::getRequestInput());
				$page->saveNewVersion();
				return self::SUCCESS;
			}
			elseif ($this->method === 'POST') {
				$this->requireAccess(Session::ACCESS_WRITE);
				$page->importForm($this->params);
				$page->saveNewVersion();
				return self::SUCCESS;
			}
			else {
				throw new APIException('Invalid request method', 405);
			}
		}
		elseif ($param === 'html') {
			$this->requireMethod('GET');
			http_response_code(200);
			header('Content-Type: text/html; charset=utf-8', true);
			echo $page->html();
			return null;
		}
		elseif ($param === 'children') {
			$this->requireMethod('GET');
			return [
				'categories' => array_map(fn($p) => $p->asArray(true), PWeb::listCategories($page->uri)),
				'pages' => array_map(fn($p) => $p->asArray(true), PWeb::listPages($page->uri)),
			];
		}
		elseif ($param === 'attachments') {
			$this->requireMethod('GET');
			return $page->listAttachments();
		}
		else {
			$this->requireMethod('GET');
			$attachment = Files::get(File::CONTEXT_WEB . '/' . $page->uri . '/' . $param);

			if (!$attachment) {
				throw new APIException('Attachment not found', 404);
			}

			$attachment->serve();
			return null;
		}
	}
}
