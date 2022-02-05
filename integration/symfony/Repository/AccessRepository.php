<?php

namespace App\Repository;

use App\Entity\Access;
use DeLoachTech\Zepher\AccessValueObject;
use DeLoachTech\Zepher\FeeProviderPersistenceInterface;
use DeLoachTech\Zepher\PersistenceClassInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method Access|null find($id, $lockMode = null, $lockVersion = null)
 * @method Access|null findOneBy(array $criteria, array $orderBy = null)
 * @method Access[]    findAll()
 * @method Access[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccessRepository extends ServiceEntityRepository implements PersistenceClassInterface, FeeProviderPersistenceInterface
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Access::class);
    }

    /**
     * Zepher/PersistenceClassInterface method
     * @param AccessValueObject $accessValueObject
     * @return void
     */
    public function getAccessValues(AccessValueObject $accessValueObject)
    {
        $values = $this->getLatestAccessValues($accessValueObject->getAccountId());

        $accessValueObject
            ->setVersionId($values['version_id'] ?? null)
            ->setActivated($values['activated'] ?? null)
            ->setLastProcess($values['last_process'])
            ->setClosed($values['closed']);
    }

    /**
     * Zepher/PersistenceClassInterface method
     * @param AccessValueObject $accessValueObject
     * @return bool
     */
    public function setAccessValues(AccessValueObject $accessValueObject): bool
    {
        if ($access = $this->getRecord($accessValueObject->getAccountId(), $accessValueObject->getVersionId(), $accessValueObject->getActivated())) {

            return $this->updateAccessRecord(
                $access['account_id'],
                $access['version_id'],
                $access['activated'],
                $accessValueObject->getLastProcess(),
                $accessValueObject->getClosed()
            );

        } else {

            return $this->createAccessRecord(
                $accessValueObject->getAccountId(),
                $accessValueObject->getVersionId(),
                time(),
                $accessValueObject->getLastProcess(),
                $accessValueObject->getClosed()
            );
        }
    }


    /**
     * Zepher/FeeProviderPersistenceInterface method
     * @param $accountId
     * @return array
     */
    public function getAccessValueObjects($accountId): array
    {
        $array = [];

        $vos = $this->getAccountRecords($accountId);

        foreach ($vos as $vo) {
            $array[] = (new AccessValueObject($accountId))
                ->setVersionId($vo['version_id'])
                ->setActivated($vo['activated'])
                ->setClosed($vo['closed'])
                ->setLastProcess($vo['last_process']);
        }
        return $array;
    }


    /**
     * Zepher/FeeProviderPersistenceInterface method
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getAccountIdsReadyForFeeProcessing(): array
    {
        return $this->getEntityManager()->getConnection()->executeQuery("select account_id from access where coalesce(last_process,activated) < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY)) and closed is null")->fetchFirstColumn() ?? [];
    }

    // Helper methods

    private function getAccountRecords(string $accountId): array
    {
        return $this->getEntityManager()->getConnection()->executeQuery("select account_id, version_id, activated, last_process, closed from access where account_id = ?", [$accountId])->fetchAllAssociative() ?? [];
    }

    private function getRecord(string $accountId, string $versionId, int $activated)
    {
        return $this->getEntityManager()->getConnection()->executeQuery("select account_id, version_id, activated, last_process, closed from access where account_id = ? and version_id = ? and activated = ? limit 1", [$accountId, $versionId, $activated])->fetchAssociative() ?? [];
    }

    private function createAccessRecord(string $accountId, string $versionId, int $activated, ?int $lastProcess, ?int $closed): bool
    {
        if ($this->getEntityManager()->getConnection()->executeQuery("insert into access (account_id, version_id, activated, last_process, closed)  values (?,?,?,?,?)", [$accountId, $versionId, $activated, $lastProcess, $closed])->rowCount() == 1) {
            return true;
        }
        return false;
    }

    private function updateAccessRecord(string $accountId, string $versionId, int $activated, ?int $lastProcess, ?int $closed): bool
    {
        if ($this->getEntityManager()->getConnection()->executeQuery("update access set last_process = ?, closed = ? where account_id = ? and version_id = ? and activated = ?", [$lastProcess, $closed, $accountId, $versionId, $activated])->rowCount() == 1) {
            return true;
        }
        return false;
    }

    private function getLatestAccessValues($accountId)
    {
        return $this->getEntityManager()->getConnection()->executeQuery("select account_id, version_id, activated, last_process, closed from access where account_id = ? order by activated DESC limit 1", [$accountId])->fetchAssociative() ?? [];
    }


    /**
     * Zepher/FeeProviderPersistenceInterface method
     * @param $configFile
     * @return void
     */
    public function configFile($configFile)
    {

    }


}
