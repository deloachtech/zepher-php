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
    private $historyFile;

    public function setup(string $configFile, $accountId, ?string $domainId)
    {
        $info = pathinfo($configFile);
        $dir = ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '');
        $this->persistenceFile = $dir . $info['filename'] . '.accounts.json';
        $this->historyFile = $dir . $info['filename'] . '.log';
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

        $this->updateHistory($accountId, $versionId);

        return true;
    }

    private function updateHistory($accountId, string $versionId)
    {
        file_put_contents($this->historyFile, time() . "|{$accountId}|{$versionId}\n", FILE_APPEND);
    }

}