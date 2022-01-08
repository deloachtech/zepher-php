<?php

namespace DeLoachTech\Zepher;

class RemotePersistence implements PersistenceClassInterface
{

    private $config;
    private $domainId;


    public function __construct(string $apiKey){

    }

    public function setup(string $configFile, $accountId, ?string $domainId)
    {
        $this->config = json_decode(file_get_contents($configFile) ?? [], true);
        $this->domainId = $domainId;
    }

    public function getVersionId($accountId): ?string
    {
        dd($accountId);

        $accountId = "acc_wuneBJWGECxqQNUp";
        $url = "https://app.zepher.io/api/apps/{$this->config['data']['app']['api_key']}/accounts/{$accountId}/versions";

        $ch = curl_init($url);

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

        dd($response);
    }

    public function setVersionId($accountId, string $versionId): bool
    {
        // TODO: Implement setVersionId() method.
        return true;
    }
}