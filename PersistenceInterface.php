<?php

namespace DeLoachTech\AppAccessControl;

interface PersistenceInterface
{

    public function getAccountVersionId($accountId): ?string;

    public function setAccountVersionId($accountId, string $versionId): bool;

    /**
     * Gets set early in case data file is required.
     * @param $dataFile
     * @return mixed
     */
    public function setDataFile($dataFile);


}