<?php
/**
 * This file is part of the deloachtech/zepher-php package.
 *
 * (c) DeLoach Tech, LLC
 * https://deloachtech.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/**
 * Add this interface to your data persistence class to use the FeeProvider.
 */

namespace DeLoachTech\Zepher;

interface FeeProviderPersistenceInterface
{
    /**
     * Your job is to return an array of account ids you want to submit for processing.
     *
     * How you accomplish this is dependent on your billing cycle. You could select all accounts based on their creation
     * day matching the current day. You could select accounts from your access data where the last process timestamp
     * is older than n days. (Once processed, the last process timestamp is updated.)
     *
     * See docs.zepher.io for example selection queries.
     *
     * @return array An indexed array of account ids.
     */
    public function getAccountIdsReadyForFeeProcessing(): array;

    /**
     * Your job is to get all access records for the account and return an AccessValueObject for each record.
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