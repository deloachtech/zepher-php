<?php
/**
 * This class simply stores account access data using the filesystem.
 *
 * You should use a database persistence class implementing the PersistenceClassInterface provided. Pass your persistence
 * class into the Zepher constructor.
 *
 * If you decide to use the filesystem, do not upload the local version!!
 */

namespace DeLoachTech\Zepher;

class FilesystemPersistence implements PersistenceClassInterface, FeeProviderPersistenceInterface
{

    private $persistenceFile;


    public function configFile($configFile)
    {
        $info = pathinfo($configFile);
        $dir = ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '');
        $this->persistenceFile = $dir . $info['filename'] . '.access.json';
    }


    public function getAccessValues(AccessValueObject $accessValueObject)
    {
        if (file_exists($this->persistenceFile)) {
            $data = json_decode(file_get_contents($this->persistenceFile) ?? [], true);

            if ($versions = $data[$accessValueObject->getAccountId()]) {

                // Get the latest activated version.
                $end = end($versions);

                $accessValueObject
                    ->setDomainId($end['domain_id'])
                    ->setVersionId($end['version_id'] ?? null)
                    ->setActivated($end['activated'])
                    ->setLastProcess($end['last_process'] ?? null)
                    ->setClosed($end['closed'] ?? null);
            }
        }
    }

    public function setAccessValues(AccessValueObject $accessValueObject): bool
    {
        if (file_exists($this->persistenceFile)) {
            $data = json_decode(file_get_contents($this->persistenceFile) ?? [], true);
        }

        $data[$accessValueObject->getAccountId()][] = [
            'domain_id' => $accessValueObject->getDomainId(),
            'version_id' => $accessValueObject->getVersionId(),
            'activated' => $accessValueObject->getActivated()
        ];

        if (file_put_contents($this->persistenceFile, json_encode($data, JSON_PRETTY_PRINT)) === false) {
            return false;
        }
        return true;
    }


    public function getAccessValueObjects($accountId): array
    {
        $data = [];

        if (file_exists($this->persistenceFile)) {
            $data = json_decode(file_get_contents($this->persistenceFile) ?? [], true);
        }

        $accessValueObjects = [];

        foreach ($data[$accountId] as $values) {

            if (empty($values['closed'])) {

                $accessValueObjects[] = (new AccessValueObject($accountId))
                    ->setActivated($values['activated'])
                    ->setDomainId($values['domain_id'])
                    ->setVersionId($values['version_id'])
                    ->setLastProcess($values['last_processed'] ?? null);
            }
        }
        return $accessValueObjects;

    }

    public function getAccountIdsReadyForFeeProcessing(): array
    {
        // TODO: Implement getAccountIdsReadyForFeeProcessing() method.
    }

    public function config($config)
    {
        // TODO: Implement config() method.
    }

    public function deleteAccessValues($accountId): bool
    {
        // TODO: Implement deleteAccessValues() method.
    }
}