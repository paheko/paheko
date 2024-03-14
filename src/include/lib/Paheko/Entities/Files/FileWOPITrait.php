<?php

namespace Paheko\Entities\Files;

use Paheko\Files\WebDAV\WebDAV;

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

	public function getWOPIEditorHTML(bool $readonly = false): ?string
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
			'permission' => $readonly,
		]);

		$src = BASE_URL . 'wopi/files/' . $this->id(); // Fix: use random hash instead of file ID
		$ttl = time()+(3600*10);
		$token = $this->createWopiToken($ttl, $readonly);

		return $wopi->rawEditorHTML($url, $src, $token, $ttl);
	}

	protected function createWopiToken(int $ttl, bool $readonly): string
	{
		$random = substr(sha1(random_bytes(10)), 0, 10);
		$id = $this->id();
		$hash_data = compact('id', 'ttl', 'random', 'readonly');
		$hash = WebDAV::hmac($hash_data, SECRET_KEY);
		$data = sprintf('%s_%s_%s_%d', $hash, $ttl, $random, $readonly);

		return WOPI::base64_encode_url_safe($data);
	}
}
