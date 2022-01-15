<?php

/**
 * Add this interface to your data persistence class to use the FeeProvider.
 */

namespace DeLoachTech\Zepher;

interface FeeProviderPersistenceInterface
{

    /**
     * Your job is to get all access records for the account and return a AccessValueObjects for each record.
     *
     * $array = [];
     * $records = getAccountAccessRecords($accountId);
     * foreach($record as $r){
     *   $array[] = (new AccessValueObject($accountId))->setVersionId($r[...])->setActivated($r[...])->setLastProcess($r[...])->setClosed($r[...]);
     * }
     * return $array;
     *
     * Accounts rarely change their access version. You can simply pass all of them here and let the provider filter
     * out closed records. The provider has to validate status anyway, so there won't be any performance hit.
     *
     * @param $accountId
     * @return array
     */
    public function getAccessValueObjects($accountId): array;
}