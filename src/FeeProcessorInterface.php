<?php
/**
 * The interface for your fee processing class.
 *
 * Create a fee processor class that implements this interface. Pass the class into the FeeProvider constructor (along with
 * your persistence class) to begin the process.
 */
namespace DeLoachTech\Zepher;

interface FeeProcessorInterface
{

    /**
     * The current config zepher.json file being used. (In case you need it.)
     *
     * @param $configFile
     */
    public function configFile($configFile);


    /**
     * Your job is to process the fee(s) and return a bool indicating success.
     *
     * @param mixed $accountId
     * @param string $versionId
     * @param array $fees The fees from the current zepher.json file for the version id.
     * @param int $beginTimestamp The calculated billing cycle begin date.
     * @param int $endTimestamp The calculated billing cycle begin date.
     *
     * @return bool If false, the current processing will stop and an exception will be thrown.
     */
    public function processFees($accountId, string $versionId, array $fees, int $beginTimestamp, int $endTimestamp): bool;


}