<?php

/**
 * The fee provider class for use with the zepher.json data file.
 *
 * This class will pass current fees back to your fee processing class. Your job is to process those fees and pass back
 * a boolean indicating success to continue updating the record status.
 *
 * Example usage:
 *
 * $feeProvider = new FeeProvider(new YourFeeProcessorClass(), new YourDataPersistenceClass(), $yourConfigDirectory);
 * $feeProvider->process();
 *
 * Your fee processing class must extend the FeeProviderInterface provided.
 *
 * You'll probably be calling this class via a cron, looping through your accounts. For efficiency, your account selection
 * logic should include some sort of filtering and/or throttling to avoid timeouts.
 */

namespace DeLoachTech\Zepher;


use Exception;

class FeeProvider
{

    private $config;
    private $feeProcessingClass;
    private $dataPersistenceClass;

    /**
     * @param object $feeProcessingClass
     * @param object $dataPersistenceClass
     * @param string $objectFile The zepher JSON object file.
     * @throws Exception
     */
    public function __construct(
        object $feeProcessingClass,
        object $dataPersistenceClass,
        string $objectFile
    )
    {
        $this->config = json_decode(file_get_contents($objectFile), true);

        if ($feeProcessingClass instanceof FeeProcessorInterface) {
            $this->feeProcessingClass = $feeProcessingClass;
            $this->feeProcessingClass->objectFile($objectFile);
        } else {
            throw new Exception('Fee processing class must implement ' . __NAMESPACE__ . '\FeeProviderInterface');
        }

        if ($dataPersistenceClass instanceof FeeProviderPersistenceInterface) {
            $this->dataPersistenceClass = $dataPersistenceClass;
            $this->dataPersistenceClass->objectFile($objectFile);
        } else {
            throw new Exception('Data persistence class must implement ' . __NAMESPACE__ . '\FeeProviderPersistenceInterface');
        }
    }


    /**
     * Call this method when you want to process fees.
     *
     * This method will fetch all accounts ready for processing. It will send each record for processing to the
     * processFees() method of tour fee processing class. Upon success, it will update the record status for the next
     * processing event.
     *
     * @return void
     * @throws Exception
     */
    public function process(): void
    {
        $accountIds = array_unique($this->dataPersistenceClass->getAccountIdsReadyForFeeProcessing());

        foreach ($accountIds as $accountId) {

            /**
             * Validate each VO status and filter out any closed records. (A closed record will blow up the beginning
             * timestamp logic for a closed record.)
             */
            $records = [];
            foreach ($this->dataPersistenceClass->getAccessValueObjects($accountId) as $valueObject) {
                if (empty($valueObject->getClosed())) {
                   $records[] = $valueObject;
                }
            }

            /**
             * Process all unclosed records regardless of the cycle time, so we can close those that need to be closed.
             */
            if (!empty($records)) {

                /**
                 * Make sure we're sorted by the activated timestamp.
                 */
                usort($records, function ($a, $b) {
                    return $a->getActivated() <=> $b->getActivated(); // Sort ascending
                });

                $timestamp = time();

                foreach ($records as $key => $record) {

                    $beginTimestamp = $record->getLastProcess() ?? $record->getActivated();

                    $fees = [];
                    foreach ($this->config['data']['versions'][$record->getVersionId()]['fees'] as $feeId) {
                        $fees[] = $this->config['data']['fees'][$feeId];
                    }

                    /**
                     * An account should not have more than one open access record. If so, it means they've changed their
                     * access version, and fees need to be finalized.
                     */
                    $closeRecord = count($records) > 1 && $key !== array_key_last($records);

                    $endTimestamp = $closeRecord ? $records[$key + 1]->getActivated() : $timestamp;

                    if ($this->feeProcessingClass->processFees($accountId, $record->getVersionId(), $fees, $beginTimestamp, $endTimestamp) == false) {
                        throw new Exception("Failed to process fees for {$accountId}:{$record->getVersionId()}:{$record->getActivated()}");
                    }

                    $record->setLastProcess($endTimestamp);

                    if ($closeRecord) {
                        $record->setClosed($endTimestamp);
                    }

                    if ($this->dataPersistenceClass->setAccessValues($record) == false) {
                        throw new Exception("Processed fees (if any), but failed to update status for  {$accountId}:{$record->getVersionId()}:{$record->getActivated()}");
                    }
                }
            }
        }
    }
}