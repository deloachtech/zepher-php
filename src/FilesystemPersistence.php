<?php
/**
 * This class simply stores account access versions using the filesystem.
 *
 * You should use a database for this purpose by creating a persistence
 * class implementing the PersistenceClassInterface provided. Pass your
 * persistence class into the AppAccess constructor.
 *
 * If you decide to use the filesystem, do not upload the local version!!
 */

namespace DeLoachTech\Zepher;

class FilesystemPersistence implements PersistenceClassInterface
{
    private $persistenceFile;
    private $defaultVersionId;

    public function setup(string $dataFile, $accountId, string $defaultVersionId)
    {
        $info = pathinfo($dataFile);
        $this->persistenceFile = ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '') . $info['filename']  . '.accounts.json';
        $this->defaultVersionId = $defaultVersionId;
    }

    public function getVersionId($accountId): ?string
    {
        $versionId = $this->defaultVersionId;
        $setVersionId = true;

        if(file_exists($this->persistenceFile)) {
            $data = json_decode(file_get_contents($this->persistenceFile) ?? [], true);
            if(!empty($data[$accountId])){
                $setVersionId = false;
                $versionId = $data[$accountId];
            }
        }

        if($setVersionId == true){
            $this->setVersionId($accountId, $versionId);
        }
        return $versionId;
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