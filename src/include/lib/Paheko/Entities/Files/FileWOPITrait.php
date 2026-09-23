<?php
declare(strict_types=1);

namespace Paheko\Entities\Files;

use Paheko\Files\Files;
use Paheko\Files\WebDAV\WebDAV;
use Paheko\Users\Session;
use Paheko\Utils;

use KD2\WebDAV\WOPI;

use const Paheko\{BASE_URL, LOCAL_SECRET_KEY};

trait FileWOPITrait
{
	public function getWopiURL(?string $action = null): ?string
	{
		$data = Files::getWOPIDiscovery();

		if (null === $data) {
			return null;
		}

		$ext = $this->extension();
		$url = null;

		if ($ext && $action) {
			$url = $data['extensions'][$ext][$action] ?? null;
		}
		elseif ($action) {
			$url = $data['mimetypes'][$this->mime][$action] ?? null;
		}
		elseif ($ext && isset($data['extensions'][$ext])) {
			$url = current($data['extensions'][$ext]);
		}
		elseif (isset($data['mimetypes'][$this->mime])) {
			$url = current($data['mimetypes'][$this->mime]);
		}

		return $url;
	}

	public function getWOPIEditorHTML(?Session $session = null, bool $readonly = false, bool $frame_only = false): ?string
	{
		// Never use WOPI editor for internal preview types (eg. text/plain)
		if (in_array($this->mime, self::PREVIEW_TYPES, true)) {
			return null;
		}

		$url = $this->getWopiURL('edit');

		if (!$url) {
			return null;
		}

		$wopi = new WOPI;
		$url = $wopi->setEditorOptions($url, [
			// Undocumented editor parameters
			// see https://github.com/nextcloud/richdocuments/blob/2338e2ff7078040d54fc0c70a96c8a1b860f43a0/src/helpers/url.js#L49
			'lang' => 'fr',
			//'closebutton' => 1,
			//'revisionhistory' => 1,
			//'title' => 'Test',
			'permission' => $readonly ? 'readonly' : '',
		]);

		$src = $this->getWOPIFileURL();
		$ttl = $this->getWOPITokenTTL();
		$token = $this->createWopiToken($readonly, $session ? $session::getUserId() : null);

		if ($frame_only) {
			return $wopi->getEditorFrameHTML($url, $src, $token, $ttl);
		}
		else {
			return $wopi->rawEditorHTML($url, $src, $token, $ttl);
		}
	}

	public function getWOPIFileURL(): string
	{
		return BASE_URL . 'wopi/files/' . $this->hash_id;
	}

	public function getWOPITokenTTL(): int
	{
		// Tokens are valid for 10 hours
		return time() + (3600*10);
	}

	public function createWopiToken(bool $readonly, ?int $user_id): string
	{
		$ttl = $this->getWOPITokenTTL();
		$random = substr(sha1(random_bytes(10)), 0, 10);
		$hash_id = $this->hash_id;
		$user_id = (int) $user_id;
		$hash_data = compact('hash_id', 'ttl', 'random', 'readonly', 'user_id');
		$hash = WebDAV::hmac($hash_data, LOCAL_SECRET_KEY);
		$data = sprintf('%s_%s_%s_%d_%d', $hash, $ttl, $random, $readonly, $user_id);

		return WOPI::base64_encode_url_safe($data);
	}
}
