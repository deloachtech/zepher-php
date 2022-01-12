<?php

/**
 * A fee processor class for use with the zepher.io data.
 *
 * This class provides methods for you to process fees associated with
 * domain version(s) selected by an account. (See the interface for more
 * information.)
 *
 * To use this class, create your fee processing class and implement the
 * FeeProcessorInterface provided. Instantiate this class and pass the
 * account(s) for processing (usually via a cron). For efficiency, your
 * account selection logic should include some sort of filtering and/or
 * throttling to avoid timeouts.
 */
namespace DeLoachTech\Zepher;


use Exception;

class FeeProcessor
{

    private $config;
    private $feeProcessingClass;

    public function __construct(
        object $feeProcessingClass,
        string $configFileDirectory = __DIR__
    )
    {
        $configFile = $configFileDirectory . DIRECTORY_SEPARATOR . 'zepher.json';
        $devFile = $configFileDirectory . DIRECTORY_SEPARATOR . 'zepher_dev.json';

        if (file_exists($devFile)) {
            $configFile = $devFile;
        } elseif (file_exists($configFile) == false) {
            throw new Exception('Unknown zepher config file ' . $configFile);
        }

        $this->config = json_decode(file_get_contents($configFile), true);

        if ($feeProcessingClass instanceof FeeProcessorInterface) {
            $this->feeProcessingClass = $feeProcessingClass;
            $this->feeProcessingClass->setup($configFile);
        } else {
            throw new Exception('Fee processing class must implement ' . __NAMESPACE__ . '\FeeProcessorInterface');
        }
    }


    /**
     * @param $accountId
     * @return void
     * @throws Exception
     */
    public function processFees($accountId): void
    {
        $accountCreatedDay = date('d', $this->feeProcessingClass->getAccountCreationDate($accountId));

        /**
         * Keep the monthly cycle days <=28, so we can eliminate the complexities
         * of uneven days in a month. This way all monthly fee processing will
         * happen from the 1st through the 28th.
         */
        $billingCycleDays = $accountCreatedDay >= 28 ? 28 : $accountCreatedDay;

        /**
         * Process all unclosed account access records regardless of the cycle
         * time, so we can close those that need to be closed.
         */
        if ($records = $this->feeProcessingClass->getOpenRecords($accountId)) {

            usort($records, function ($item1, $item2) {
                return $item1['activated'] <=> $item2['activated'];
            });

            foreach ($records as $key => $record) {

                $beginTimestamp = $record['last_process'] ?? $record['activated'];

                /**
                 * An account should not have more than one open access record.
                 * If so, it means they've changed their access version, and we
                 * must finalize any recurring fees.
                 */
                $closeRecord = count($records) > 1 && $key !== array_key_last($records);

                if ($closeRecord) {

                    // The end timestamp is the next version activated timestamp.
                    $endTimestamp = $records[$key + 1]['activated'];

                    if($this->feeProcessingClass->processFees($record['record_id'], $accountId, $record['version_id'], $this->config['data']['versions'][$record['version_id']]['fees'] ?? [], $beginTimestamp, $endTimestamp) == false){
                        throw new Exception('Failed to process fee(s) for record id '.$record['record_id']);
                    }

                    if($this->feeProcessingClass->updateRecordStatus($record['record_id'], $endTimestamp, $endTimestamp) == false){
                        throw new Exception('Processed fees, but failed to update status for record id '.$record['record_id']);
                    }

                } else {

                    // The end timestamp is the beginning timestamp plus the account billing cycle days in seconds.
                    $endTimestamp = $beginTimestamp + (60 * 60 * 24 * $billingCycleDays);

                    if ($endTimestamp <= time()) {

                        if($this->feeProcessingClass->processFees($record['record_id'], $accountId, $record['version_id'], $this->config['data']['versions'][$record['version_id']]['fees'] ?? [], $beginTimestamp, $endTimestamp) == false){
                            throw new Exception('Failed to process fee(s) for record id '.$record['record_id']);
                        }

                        if($this->feeProcessingClass->updateRecordStatus($record['record_id'], $endTimestamp, null) == false){
                            throw new Exception('Processed fees, but failed to update status for record id '.$record['record_id']);
                        }
                    }
                }
            }
        }
    }
}