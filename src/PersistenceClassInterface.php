<?php

namespace DeLoachTech\Zepher;

interface PersistenceClassInterface
{
    /**
     * Gets called early in the process and passes convenience information to the persistence class for use.
     * @param string $dataFile The data file path and file name.
     * @param mixed $accountId The current account id (if any).
     * @param string $defaultVersionId The default version id passed in the access class constructor.
     */
    public function setup(string $dataFile, $accountId, string $defaultVersionId);


    /**
     * Your job is to return the current account id.
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