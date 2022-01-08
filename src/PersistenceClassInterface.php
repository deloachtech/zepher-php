<?php

namespace DeLoachTech\Zepher;

interface PersistenceClassInterface
{
    /**
     * Gets called early in the process and passes convenience information to the persistence class for use.
     * @param string $configFile The zepher.json file.
     * @param mixed $accountId The current account id (if any).
     * @param mixed $domainId The current domain id.
     */
    public function setup(string $configFile, $accountId, ?string $domainId);


    /**
     * Your job is to return the current account version id.
     * @param mixed $accountId
     * @return string|null
     */
    public function getVersionId($accountId): ?string;


    /**
     * Your job is to store the version id using the arguments provided and return a bool.
     * @param mixed $accountId The active account id.
     * @param string $versionId The active account access version id.
     * @return bool
     */
    public function setVersionId($accountId, string $versionId): bool;


}