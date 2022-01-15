<?php
/**
 * This class remotely stores account access data.
 *
 * Most accounts will rarely change their access version, yet the value is required on each request. This class caches
 * the remotely stored version data until the account changes their version.
 *
 * The cache refreshes every hour.
 *
 * The difference between this and filesystem persistence is the data integrity. If the filesystem file is lost, so is
 * the data for *every* account, resulting in each account being re-assigned the default version.
 *
 * You should exclude the cache file in your deployment strategy.
 */

namespace DeLoachTech\Zepher;

class RemotePersistence implements PersistenceClassInterface
{

    private $config;
    private $apiKey;
    private $cache;
    private $refresh = 60 * 60; // Every hour


    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->cache = __DIR__ . '/RemotePersistenceCache.php';
        $this->refreshCache();
    }

    public function configFile($configFile)
    {
        $this->config = json_decode(file_get_contents($configFile) ?? [], true);
    }

    public function getAccessValues(AccessValueObject $accessValueObject)
    {
        if ($data = $this->getCachedValues($accessValueObject->getAccountId())) {
            $accessValueObject
                ->setVersionId($data['version_id'])
                ->setActivated($data['activated'])
                ->setLastProcess($data['last_process'])
                ->setClosed($data['closed']);

        } else {

            $url = "https://app.zepher.io/api/apps/{$this->config['data']['app']['id']}/accounts/{$accessValueObject->getAccountId()}/versions";
            $response = $this->curl($url, null);

            if ($data = $response['data']) {
                $accessValueObject
                    ->setVersionId($data['version_id'])
                    ->setActivated($data['activated'])
                    ->setLastProcess($data['last_process'])
                    ->setClosed($data['closed']);

                $this->setCachedValues($accessValueObject);
            }
        }
    }

    public function setAccessValues(AccessValueObject $accessValueObject): bool
    {
        $url = "https://app.zepher.io/api/apps/{$this->config['data']['app']['id']}/accounts/{$accessValueObject->getAccountId()}/versions";

        $response = $this->curl($url, [
            'account_id' => $accessValueObject->getAccountId(),
            'version_id' => $accessValueObject->getVersionId(),
            'activated' => $accessValueObject->getActivated(),
            'last_process' => $accessValueObject->getLastProcess(),
            'closed' => $accessValueObject->getClosed()
        ]);

        if ($data = $response['data']) {
            $accessValueObject
                ->setVersionId($data['version_id'])
                ->setActivated($data['activated'])
                ->setLastProcess($data['last_process'])
                ->setClosed($data['closed']??null);

            $this->setCachedValues($accessValueObject);

            return true;
        }
        return false;
    }


    private function getCachedValues($accountId): ?array
    {
        $cache = [];
        if (file_exists($this->cache)) {
            $cache = unserialize(file_get_contents($this->cache)) ?? [];
        }
        return $cache[$accountId] ?? null;
    }


    private function setCachedValues(AccessValueObject $accessValueObject)
    {
        $cache = [];
        if (file_exists($this->cache)) {
            $cache = unserialize(file_get_contents($this->cache)) ?? [];
        }
        $cache[$accessValueObject->getAccountId()] = [
            'version_id' => $accessValueObject->getVersionId(),
            'activated' => $accessValueObject->getActivated(),
            'last_process' => $accessValueObject->getLastProcess(),
            'closed' => $accessValueObject->getClosed()
        ];
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