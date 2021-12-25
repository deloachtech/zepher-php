<?php
/**
 * This class stores access data using the filesystem.
 *
 * You should use a database for storing access information. Create a
 * persistence class and implement the PersistenceClassInterface provided.
 * Pass your persistence class into the AppAccess constructor.
 *
 * If you decide to use the filesystem, do not upload the local version!!
 */

namespace DeLoachTech\AppAccess;

class FilesystemPersistence implements PersistenceClassInterface
{
    private $persistenceFile;

    public function setup($dataFile)
    {
        $info = pathinfo($dataFile);
        $this->persistenceFile = ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '') . $info['filename']  . '.accounts.json';
    }

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

}