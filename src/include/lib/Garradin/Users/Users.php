<?php

namespace Garradin\Users;

use Garradin\Entities\Users\User;

use Garradin\DynamicList;

use KD2\DB\EntityManager as EM;

class Users
{
	static public function listByCategory(?int $id_category = null): DynamicList
	{
		$df = DynamicFields::getInstance();

		$columns = [
			'_user_id' => [
				'select' => 'id',
			],
			'number' => [
				'label' => 'Num.',
				'select' => $df->getNumberField(),
			],
			'identity' => [
				'label' => $df->getNameLabel(),
				'select' => $df->getNameFieldsSQL(),
			]
		];

		$fields = $df->getListedFields();

		foreach ($fields as $key => $config) {
			if (isset($columns[$key])) {
				continue;
			}

			$columns[$key] = [
				'label' => $config->label,
			];
		}

		$tables = User::TABLE;
		$conditions = $id_category ? sprintf('id_category = %d', $id_category) : sprintf('id_category IN (SELECT id FROM users_categories WHERE hidden = 0)');

		$order = 'identity';

		if (!isset($columns[$order])) {
			$order = key($fields) ?? 'number';
		}

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy($order, false);

		return $list;
	}

	static public function get(int $id): ?User
	{
		return EM::findOneById(User::class, $id);
	}

	static public function getName(int $id): ?string
	{
		$name = DynamicFields::getNameFieldsSQL();
		return EM::getInstance(User::class)->col(sprintf('SELECT %s FROM @TABLE WHERE id = ?;', $name), $id);
	}

	static public function deleteMultiple(array $ids): void
	{
		$session = Session::getInstance();

		if ($session->isLogged()) {
			$user = $session->getUser();

			foreach ($ids as $id) {
				if ($user->id == $id) {
					throw new UserException('Il n\'est pas possible de supprimer son propre compte.');
				}
			}
		}

		foreach ($ids as &$id)
		{
			$id = (int) $id;
			Files::delete(File::CONTEXT_USER . '/' . $id);
		}

		$db = DB::getInstance();

		// Suppression du membre
		$db->delete(User::TABLE, $db->where('id', $membres));
	}

	static public function changeCategory(int $category_id, array $ids)
	{
		$session = Session::getInstance();
		$user_id = null;

		if ($session->isLogged()) {
			$user_id = $session->getUser()->id;
		}

		foreach ($ids as &$id) {
			$id = (int) $id;

			// Don't allow current user ID to change his/her category
			// as that means he/she could be logged out
			if ($id == $user_id) {
				$id = null;
			}
		}

		unset($id);

		// Remove logged-in user ID
		$ids = array_filter($ids);

		$db = DB::getInstance();
		return $db->update(User::TABLE,
			['id_category' => $category_id],
			$db->where('id', $ids)
		);
	}

	public function sendMessage(array $recipients, $subject, $message, $send_copy)
	{
		$config = Config::getInstance();

		$emails = [];

		foreach ($recipients as $key => $recipient)
		{
			// Ignorer les destinataires avec une adresse email vide
			if (empty($recipient->email))
			{
				continue;
			}

			if (!isset($recipient->email, $recipient->id)) {
				throw new UserException('Il manque l\'identifiant ou l\'email dans le résultat');
			}

			// Refuser d'envoyer un mail à une adresse invalide, sans vérifier le MX
			// sinon ça serait trop lent
			if (!SMTP::checkEmailIsValid($recipient->email, false))
			{
				throw new UserException(sprintf('Adresse email invalide : "%s". Aucun message n\'a été envoyé.', $recipient->email));
			}

			// This is to avoid having duplicate emails
			$emails[$recipient->email] = $recipient->id;
		}

		if (!count($emails)) {
			throw new UserException('Aucun destinataire de la liste ne possède d\'adresse email.');
		}

		foreach ($emails as $email => $id)
		{
			Utils::sendEmail(Utils::EMAIL_CONTEXT_BULK, $email, $subject, $message, $id);
		}

		if ($send_copy)
		{
			Utils::sendEmail(Utils::EMAIL_CONTEXT_BULK, $config->get('email_asso'), $subject, $message);
		}

		return true;
	}
}