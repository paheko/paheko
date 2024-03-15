<?php
declare(strict_types=1);

namespace Paheko\Entities\Files;

use Paheko\Files\WebDAV\WebDAV;
use Paheko\Users\Session;

use KD2\WebDAV\WOPI;

use const Paheko\{WOPI_DISCOVERY_URL, SHARED_CACHE_ROOT, BASE_URL, SECRET_KEY};

trait FileWOPITrait
{
	public function getWopiURL(?string $action = null): ?string
	{
		if (!WOPI_DISCOVERY_URL) {
			return null;
		}

		$cache_file = sprintf('%s/wopi_%s.json', SHARED_CACHE_ROOT, md5(WOPI_DISCOVERY_URL));
		static $data = null;

		if (null === $data) {
			// We are caching discovery for 15 days, there is no need to request the server all the time
			if (file_exists($cache_file) && filemtime($cache_file) >= 3600*24*15) {
				$data = json_decode(file_get_contents($cache_file), true);
			}

			if (!$data) {
				try {
					$data = WOPI::discover(WOPI_DISCOVERY_URL);
					file_put_contents($cache_file, json_encode($data));
				}
				catch (\RuntimeException $e) {
					return null;
				}
			}
		}

		$ext = $this->extension();
		$url = null;

		if ($action) {
			$url = $data['extensions'][$ext][$action] ?? null;
			$url ??= $data['mimetypes'][$this->mime][$action] ?? null;
		}
		elseif (isset($data['extensions'][$ext])) {
			$url = current($data['extensions'][$ext]);
		}
		elseif (isset($data['mimetypes'][$this->mime])) {
			$url = current($data['mimetypes'][$this->mime]);
		}

		return $url;
	}

	public function getWOPIEditorHTML(Session $session = null, bool $readonly = false, bool $frame_only = false): ?string
	{
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

		$src = BASE_URL . 'wopi/files/' . $this->hash_id;
		$ttl = time()+(3600*10);
		$token = $this->createWopiToken($ttl, $readonly, $session ? $session::getUserId() : null);

		if ($frame_only) {
			return $wopi->getEditorFrameHTML($url, $src, $token, $ttl);
		}
		else {
			return $wopi->rawEditorHTML($url, $src, $token, $ttl);
		}
	}

	protected function createWopiToken(int $ttl, bool $readonly, ?int $user_id): string
	{
		$random = substr(sha1(random_bytes(10)), 0, 10);
		$hash_id = $this->hash_id;
		$user_id = (int) $user_id;
		$hash_data = compact('hash_id', 'ttl', 'random', 'readonly', 'user_id');
		$hash = WebDAV::hmac($hash_data, SECRET_KEY);
		$data = sprintf('%s_%s_%s_%d_%d', $hash, $ttl, $random, $readonly, $user_id);

		return WOPI::base64_encode_url_safe($data);
	}
}
