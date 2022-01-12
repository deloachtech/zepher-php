<?php
/**
 * This class simply stores account access versions using the filesystem.
 *
 * You should use a database persistence class implementing the
 * PersistenceClassInterface provided. Pass your persistence class into
 * the Zepher constructor.
 *
 * If you decide to use the filesystem, do not upload the local version!!
 */

namespace DeLoachTech\Zepher;

class FilesystemPersistence implements PersistenceClassInterface
{
    private $persistenceFile;

    public function setup(string $configFile, $accountId, ?string $domainId)
    {
        $info = pathinfo($configFile);
        $dir = ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '');
        $this->persistenceFile = $dir . $info['filename'] . '.accounts.json';
    }

    public function getVersionId($accountId): ?string
    {
        if (file_exists($this->persistenceFile)) {
            $data = json_decode(file_get_contents($this->persistenceFile) ?? [], true);
            return $data[$accountId] ?? null;
        }
        return null;
    }

    public function setVersionId($accountId, string $versionId): bool
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