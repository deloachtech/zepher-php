<?php
/**
 * The interface for the Zepher FeeProcessor.
 *
 * To use the FeeProcessorClass, you must include the following values in your
 * record storage schema:
 *
 *   The record id.
 *   The version id.
 *   The version activation date value that can be converted into an integer timestamp.
 *   The last processed date value that can be converted into an integer timestamp.
 *   The closed date value that can be converted into an integer timestamp.
 *
 * The key names for these values can be those defied in this getOpenRecords() method,
 * or they can be mapped in the method call from your key names.
 *
 */
namespace DeLoachTech\Zepher;

interface FeeProcessorInterface
{

    /**
     * Gets called early in the process and passes convenience information to the fee
     * processing class for use.
     * @param string $configFile The zepher.json file.
     */
    public function setup(string $configFile);


    /**
     * Your job is to return an indexed array of all UNCLOSED access records
     * for the account id provided. Each record in the array MUST include
     * the following keys and values.
     *
     *   `record_id`, Your record id you created from Zepher->setVersionId().
     *   `version_id`, Your value from the Zepher->setVersionId().
     *   `activated` Your value from the Zepher->setVersionId().
     *   `last_process`, The value you saved from this class updateRecordStatus().
     *
     * Your record storage should also have a field for a `closed` timestamp that
     * will be passed to you from this updateRecordStatus() method.
     *
     * Note: If you pass in a closed record, you'll blow up the world!
     *
     * @param $accountId
     * @return array
     */
    public function getOpenRecords($accountId):array;

    /**
     * Your job is to return the creation date timestamp for the account id
     * provided. The creation date is used to determine the billing cycle days.
     * You could use another timestamp, but the creation date lives with the
     * account and never changes.
     * @param $accountId
     * @return int timestamp
     */
    public function getAccountCreationDate($accountId): int;


    /**
     * Your job is to update the record status and return a bool indicating success.
     *
     * You'll always be updating the lastProcess timestamp. However, the close record
     * timestamp returned to you may be null, indicating to keep the record open.
     *
     * @param mixed $recordId The value you provided in this getOpenRecords()
     * @param int $lastProcess Update your last_process field with this timestamp.
     * @param int|null $closeRecord If set, close the record for further processing with the timestamp provided.
     * @return bool If false, the current processing will stop and an exception will be thrown.
     */
    public function updateRecordStatus($recordId, int $lastProcess, int $closeRecord = null):bool;


    /**
     * Your job is to process the fees and return a bool indicating success.
     *
     * These values will be passed back to you for convenience from this getOpenRecords().
     *
     * @param mixed $recordId The value you provided in this getOpenRecords().
     * @param mixed $accountId The value you provided in this getOpenRecords().
     * @param string $versionId The value you provided in this getOpenRecords().
     *
     * These values will be provided by FeeProcessor for the current event.
     *
     * @param array $fees The fees from the current zepher.json file for the version id you provided.
     * @param int $beginTimestamp The calculated billing cycle begin date.
     * @param int $endTimestamp The calculated billing cycle begin date.
     *
     * @return bool If false, the current processing will stop and an exception will be thrown.
     */
    public function processFees($recordId, $accountId, string $versionId, array $fees, int $beginTimestamp, int $endTimestamp): bool;


}