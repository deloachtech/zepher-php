<?php

/**
 * The fee provider class for use with the zepher.json data file.
 *
 * This class will pass current fees back to your fee processing class. Your job is to process those fees and pass back
 * a boolean indicating success to continue updating the record status.
 *
 * Example usage:
 *
 * $feeProvider = new FeeProvider(new YourFeeProcessorClass(), new YourDataPersistenceClass(), __DIR__);
 *
 * $accounts = getAccounts();
 *
 * foreach($accounts as $account){
 *    $feeProvider->processFee($account->getId());
 * }
 *
 * Your fee processing class must extend the FeeProviderInterface provided.
 *
 * You'll probably be calling this class via a cron, loping through your accounts. For efficiency, your account selection
 * logic should include some sort of filtering and/or throttling to avoid timeouts.
 */

namespace DeLoachTech\Zepher;


use Exception;

class FeeProvider
{

    private $config;
    private $feeProcessingClass;
    private $dataPersistenceClass;

    public function __construct(
        object $feeProcessingClass,
        object $dataPersistenceClass,
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

        if ($feeProcessingClass instanceof FeeProviderInterface) {

            $this->feeProcessingClass = $feeProcessingClass;
            $this->dataPersistenceClass = $dataPersistenceClass;

            $this->dataPersistenceClass->setConfigFile($configFile);
            $this->feeProcessingClass->configFile($configFile);

        } else {
            throw new Exception('Fee processing class must implement ' . __NAMESPACE__ . '\FeeProviderInterface');
        }
    }


    public function processFees($accountId): void
    {
        $accessValueObject = new AccessValueObject($accountId);

        $this->dataPersistenceClass->getAccessValues($accessValueObject);

        /**
         * Keep the monthly cycle days <=28, so we can eliminate the complexities of uneven days in a month. This way
         * all monthly fee processing will happen from the 1st through the 28th.
         */
        $accountDayOfMonth =  $this->feeProcessingClass->getBillingDayOfMonth($accountId);
        if($accountDayOfMonth >0 && $accountDayOfMonth <= 28){
            $billingCycleDay = $accountDayOfMonth;
        }elseif ($accountDayOfMonth >28){
            $billingCycleDay = 28;
        }else{
            throw new Exception("Invalid account billing day of month for {$accountId}. Expected a value between 1-28, got {$accountDayOfMonth}.");
        }

        /**
         * It's our responsibility to validate each VO status and filter out any closed records. The user could pass in
         * a closed record and blow the world up!
         */
        $valueObjects = $this->dataPersistenceClass->getAccessValueObjects($accountId) ??[];
        $records = [];
        foreach ($valueObjects as $valueObject){
            if(!empty($valueObject->getClosed())){
                $records[] = $valueObject;
            }
        }

        /**
         * Process all unclosed account access records regardless of the cycle time, so we can close those that need to
         * be closed.
         */
        if (!empty($records)) {

            // Make sure we're sorted by the activated timestamp
            usort($records, function ($a, $b) {
                return $a->getActivated() <=> $b->getActivated();
            });

            foreach ($records as $key => $record) {

                $beginTimestamp = $record->getLastProcess() ?? $record->getActivated();

                /**
                 * An account should not have more than one open access record. If so, it means they've changed their
                 * access version, and we must finalize any recurring fees.
                 */
                $closeRecord = count($records) > 1 && $key !== array_key_last($records);

                if ($closeRecord) {

                    // The end timestamp is the next version activated timestamp.
                    $endTimestamp = $records[$key + 1]['activated'];

                    if ($this->feeProcessingClass->processFees(
                            $accountId,
                            $record->getVersionId(),
                            $this->config['data']['versions'][$record->getVersionId()]['fees'] ?? [],
                            $beginTimestamp,
                            $endTimestamp
                        ) == false) {
                        throw new Exception("Failed to process fees for {$accountId}:{$record->getVersionId()}:{$record->getActivated()}");
                    }

                    $accessValueObject
                        ->setLastProcess($endTimestamp)
                        ->setClosed($endTimestamp);

                    if ($this->dataPersistenceClass->setAccessValues($accessValueObject) == false) {
                        throw new Exception("Processed fees (if any), but failed to update status for  {$accountId}:{$record->getVersionId()}:{$record->getActivated()}");
                    }

                } else {

                    // The end timestamp is the beginning timestamp plus the account billing cycle days in seconds.
                    $endTimestamp = $beginTimestamp + (60 * 60 * 24 * $billingCycleDay);

                    if ($endTimestamp <= time()) {

                        if ($this->feeProcessingClass->processFees(
                                $accountId,
                                $record->getVersionId(),
                                $this->config['data']['versions'][$record->getVersionId()]['fees'] ?? [],
                                $beginTimestamp,
                                $endTimestamp
                            ) == false) {
                            throw new Exception("Failed to process fees for {$accountId}:{$record->getVersionId()}:{$record->getActivated()}");
                        }

                        $accessValueObject->setLastProcess($endTimestamp);

                        if ($this->dataPersistenceClass->setAccessValues($accessValueObject) == false) {
                            throw new Exception("Processed fees (if any), but failed to update status for  {$accountId}:{$record->getVersionId()}:{$record->getActivated()}");
                        }
                    }
                }
            }
        }
    }
}