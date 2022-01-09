<?php
/**
 * This class remotely stores account access versions (and metadata).
 *
 * An account will RARELY (if ever) change their access version, yet
 * the value is required on each request. Therefore, this class caches
 * remote results until the account changes their version.
 *
 * The cache refreshes every hour.
 *
 * The difference between this and filesystem persistence is the data
 * integrity. If the filesystem file is lost, so is *every* accounts'
 * data.
 *
 * You should exclude the cache file in your deployment strategy.
 */

namespace DeLoachTech\Zepher;

class RemotePersistence implements PersistenceClassInterface
{

    private $config;
    private $apiKey;
    private $cache;
    private $refresh = 60*60; // Every hour


    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->cache = __DIR__ . '/RemotePersistenceCache.php';
        $this->refreshCache();
    }

    public function setup(string $configFile, $accountId, ?string $domainId)
    {
        $this->config = json_decode(file_get_contents($configFile) ?? [], true);
    }


    public function getVersionId($accountId): ?string
    {
        if ($versionId = $this->getCachedVersion($accountId)) {
            return $versionId;
        }

        $url = "https://app.zepher.io/api/apps/{$this->config['data']['app']['id']}/accounts/{$accountId}/versions";
        $response = $this->curl($url, null);

        if ($response['data']['id']) {
            $this->setCachedVersion($accountId, $response['data']['id']);
        }
        return $response['data']['id'] ?? null;
    }

    public function setVersionId($accountId, string $versionId): bool
    {
        $url = "https://app.zepher.io/api/apps/{$this->config['data']['app']['id']}/accounts/{$accountId}/versions";
        $response = $this->curl($url, ['id' => $versionId]);
        if (!empty($response['data']['id'])) {
            $this->setCachedVersion($accountId, $versionId);
            return true;
        }
        return false;
    }


    private function getCachedVersion($accountId)
    {
        $cache = [];
        if (file_exists($this->cache)) {
            $cache = unserialize(file_get_contents($this->cache)) ?? [];
        }
        return $cache[$accountId] ?? null;
    }


    private function setCachedVersion($accountId, string $versionId)
    {
        $cache = [];
        if (file_exists($this->cache)) {
            $cache = unserialize(file_get_contents($this->cache)) ?? [];
        }
        $cache[$accountId] = $versionId;
        file_put_contents($this->cache, serialize($cache));
    }


    private function refreshCache()
    {
        if (file_exists($this->cache)) {
            if ((time() - filectime($this->cache)) > ($this->refresh)) {
                $cache = [];
                file_put_contents($this->cache, serialize($cache));
            }
        }
    }

    /**
     * Will do a POST if there are fields, otherwise, a GET
     * @param $url
     * @param array|null $fields $k => $v pairs
     * @return array a PHP array of the results
     */
    private function curl($url, ?array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-AUTH-APIKEY: ' . $this->apiKey]);

        if (!empty($fields)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        if (is_resource($ch)) {
            curl_close($ch);
        }

        if (0 !== $errno) {
            throw new \RuntimeException($error, $errno);
        }


        return json_decode($response, true) ?? [];
    }


}