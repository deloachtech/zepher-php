<?php

namespace DeLoachTech\AppAccess;

interface PersistenceClassInterface
{
    /**
     * Gets called early in the process and passes information to the persistence class for use.
     * @param string $dataFile The data file path amd file name.
     */
    public function setup(string $dataFile);


    /**
     * Your job is to return the current account ID.
     * @param mixed $accountId
     * @return string|null
     */
    public function getAccountVersionId($accountId): ?string;


    /**
     * Your job is to store the version ID using the arguments provided.
     * @param mixed $accountId The active account ID
     * @param string $versionId The active account access version ID
     * @return bool
     */
    public function setAccountVersionId($accountId, string $versionId): bool;


}