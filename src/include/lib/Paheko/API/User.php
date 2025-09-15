<?php

namespace Paheko\API;

use Paheko\Users\Categories;
use Paheko\Users\DynamicFields;
use Paheko\Users\Users;
use Paheko\Users\Export as UsersExport;
use Paheko\Users\Import as UsersImport;
use Paheko\Search;
use Paheko\Entities\Search as SE;
use Paheko\APIException;
use Paheko\UserException;
use Paheko\ValidationException;
use Paheko\Services\Subscriptions;
use Paheko\Users\Session;
use Paheko\Entity;
use Paheko\Utils;
use Paheko\CSV_Custom;
use const Paheko\CACHE_ROOT;

trait User
{
	protected function user(string $uri): ?array
	{
		$fn = strtok($uri, '/');
		$fn2 = strtok('/');
		strtok('');

		if ($fn === 'categories') {
			return Categories::listWithStats();
		}
		elseif ($fn === 'category') {
			$id = (int) strtok($fn2, '.');
			$format = strtok('');

			try {
				UsersExport::exportCategory($format ?: 'json', $id, true);
			}
			catch (\InvalidArgumentException $e) {
				throw new APIException($e->getMessage(), 400, $e);
			}

			return null;
		}
		elseif ($fn === 'search') {
			$q = $this->params['q'] ?? '';

			if (!$q) {
				return [];
			}

			$list = Search::simpleList(SE::TARGET_USERS, $q);
			$list->setPageSize(null);
			return iterator_to_array($list->iterate());
		}
		elseif ($fn === 'new') {
			$this->requireAccess(Session::ACCESS_WRITE);

			$user = Users::create();
			$user->importForm($this->params);
			$user->setNumberIfEmpty();

			if (empty($this->params['force_duplicate']) && $user->checkDuplicate()) {
				throw new APIException('This user seems to be a duplicate of an existing one', 409);
			}

			if (!empty($this->params['id_category'])) {
				if ($this->isSystemUser()) {
					$user->set('id_category', (int)$this->params['id_category']);
				}
				elseif (!$user->setCategorySafeNoConfig($this->params['id_category'])) {
					throw new APIException('You are not allowed to create a user in this category', 403);
				}
			}

			if (isset($this->params['password'])) {
				try {
					$user->setNewPassword([$this->params['password']], false);
				}
				catch (ValidationException $e) {
					throw new APIException($e->getMessage(), 400, $e);
				}
			}

			$user->save();

			return $user->exportAPI();
		}
		// Actions for a specific user
		elseif (ctype_digit($fn)) {
			$user = Users::get((int)$fn);

			if (!$user) {
				throw new APIException('The requested user does not exist', 404);
			}

			// Subscriptions
			if ($fn2 === 'subscribe') {
				$this->requireMethod('POST');
				$this->requireAccess(Session::ACCESS_WRITE);

				if (!$this->hasParam('id_service')) {
					throw new APIException('Missing "id_service" parameter', 400);
				}

				$params = $this->params;
				unset($params['id_user']);
				$id_service = intval($this->params['id_service']);
				$id_fee = intval($this->params['id_fee'] ?? 0) ?: null;

				$su = Subscriptions::create($user->id(), $id_service, $id_fee);
				$su->importForm($params);

				if (!$this->hasParamTrue('force_duplicate')
					&& $su->isDuplicate()) {
					throw new APIException('This user already has been subscribed to this service at this date.', 409);
				}

				$su->save();
				return $su->asArray();
			}
			elseif (!empty($fn2)) {
				throw new APIException('Unknown route', 404);
			}
			else {
				if ($this->method === 'POST') {
					$this->requireAccess(Session::ACCESS_WRITE);

					try {
						$user->validateCanBeModifiedBy(null);
					}
					catch (UserException $e) {
						throw new APIException($e->getMessage(), 403, $e);
					}

					$user->importForm($this->params);
					$user->save();
				}
				elseif ($this->method === 'DELETE') {
					$this->requireAccess(Session::ACCESS_ADMIN);

					try {
						$user->validateCanBeModifiedBy(null);
					}
					catch (UserException $e) {
						throw new APIException($e->getMessage(), 403, $e);
					}

					$user->delete();
					return self::SUCCESS;
				}

				return $user->exportAPI();
			}
		}
		elseif ($fn === 'import') {
			$this->requireHttpClient();
			$fp = null;

			if ($this->method === 'PUT') {
				$params = $this->params;
			}
			elseif ($this->method === 'POST') {
				$params = $_POST;
			}
			else {
				throw new APIException('Wrong request method', 400);
			}

			$mode = $params['mode'] ?? 'auto';

			if (!in_array($mode, ['auto', 'create', 'update'])) {
				throw new APIException('Unknown mode. Only "auto", "create" and "update" are accepted.', 400);
			}

			$this->requireAccess(Session::ACCESS_ADMIN);

			$path = tempnam(CACHE_ROOT, 'tmp-import-api');

			if ($this->method === 'POST') {
				if (empty($_FILES['file']['tmp_name']) || !empty($_FILES['file']['error'])) {
					throw new APIException('Empty file or no file was sent.', 400);
				}

				$path = $_FILES['file']['tmp_name'] ?? null;
			}
			else {
				$fp = fopen($path, 'wb');
				stream_copy_to_stream($this->file_pointer, $fp);
				fclose($fp);
				$this->closeFilePointer();
			}

			try {
				if (!filesize($path)) {
					throw new APIException('Empty CSV file', 400);
				}

				$csv = new CSV_Custom;
				$df = DynamicFields::getInstance();
				$csv->setColumns($df->listImportAssocNames());
				$required_fields = $df->listImportRequiredAssocNames($mode === 'update' ? true : false);
				$csv->setMandatoryColumns(array_keys($required_fields));
				$csv->loadFile($path);
				$csv->skip((int)($params['skip_lines'] ?? 1));

				if (!empty($params['column']) && is_array($params['column'])) {
					$csv->setIndexedTable($params['column']);
				}
				else {
					$csv->setTranslationTableAuto();
				}

				if (!$csv->loaded() || !$csv->ready()) {
					throw new APIException('Missing columns or error during columns matching of import table', 400);
				}

				if ($fn2 === 'preview') {
					$report = UsersImport::report($csv, $mode);

					$report['unchanged'] = array_map(
						fn($user) => ['id' => $user->id(), 'name' => $user->name()],
						$report['unchanged']
					);

					$report['created'] = array_map(
						fn($user) => $user->asDetailsArray(),
						$report['created']
					);

					$report['modified'] = array_map(
						function ($user) {
							$out = ['id' => $user->id(), 'name' => $user->name(), 'changed' => []];

							foreach ($user->getModifiedProperties() as $key => $value) {
								$out['changed'][$key] = ['old' => $value, 'new' => $user->$key];
							}

							return $out;
						},
						$report['modified']
					);


					return $report;
				}
				else {
					UsersImport::import($csv, $mode);
					return null;
				}
			}
			finally {
				Utils::safe_unlink($path);
			}
		}
		else {
			throw new APIException('Unknown user action', 404);
		}
	}
}
