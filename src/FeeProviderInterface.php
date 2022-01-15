<?php
/**
 * The interface for the Zepher FeeProvider.
 *
 * Create a fee processor class that implements this interface. Pass the class into the FeeProvider constructor (along with
 * your persistence class) to process your fees.
 */
namespace DeLoachTech\Zepher;

interface FeeProviderInterface
{

    /**
     * The current config file being used. (In case you need it.)
     *
     * @param $configFile
     */
    public function configFile($configFile);


    /**
     * Your job is to return a value between 1 and 28 for the accounts billing day of month. This value is used to
     * calculate the billing cycle times, and should remain fixed. You might want to simply return the day of the month
     * your company invoices in lieu of an account-based value.
     *
     * @param $accountId
     * @return int timestamp
     */
    public function getBillingDayOfMonth($accountId): int;


    /**
     * Your job is to process the fee(s) and return a bool indicating success.
     *
     * @param mixed $accountId
     * @param string $versionId
     * @param array $fees The fees from the current zepher.json file for the version id provided.
     * @param int $beginTimestamp The calculated billing cycle begin date.
     * @param int $endTimestamp The calculated billing cycle begin date.
     *
     * @return bool If false, the current processing will stop and an exception will be thrown.
     */
    public function processFees($accountId, string $versionId, array $fees, int $beginTimestamp, int $endTimestamp): bool;


}