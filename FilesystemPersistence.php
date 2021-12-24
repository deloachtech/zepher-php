<?php

namespace DeLoachTech\AppAccessControl;

class FilesystemPersistence implements PersistenceInterface
{
    private $persistenceFile;


    public function getAccountVersionId($accountId): ?string
    {
        $data = [];
        if(file_exists($this->persistenceFile)) {
            $data = json_decode(file_get_contents($this->persistenceFile) ?? [], true);
        }
        return $data[$accountId] ?? null;
    }

    public function setAccountVersionId($accountId, string $versionId): bool
    {
        if (file_exists($this->persistenceFile)) {
            $data = json_decode(file_get_contents($this->persistenceFile) ?? [], true);
        }
        $data[$accountId] = $versionId;
        if (file_put_contents($this->persistenceFile, json_encode($data, JSON_PRETTY_PRINT)) === false) {
            return false;
        }
        return true;
    }

    public function setDataFile($dataFile)
    {
        $info = pathinfo($dataFile);
        $this->persistenceFile = ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '') . $info['filename'] . '.' . 'access.json';
    }
}