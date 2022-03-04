<?php

namespace DeLoachTech\Zepher;

interface PersistenceClassInterface
{

    /**
     * The current config zepher.json file being used. (In case you need it.)
     *
     * @param $configFile
     */
    public function configFile($configFile);


    /**
     * The current Zepher configuration. (In case you need it.)
     *
     * @param $config
     */
    public function config($config);


    /**
     * Your job is to set all the current object values from your data storage. (The account id is already set in the VO.)
     *
     * @param AccessValueObject $accessValueObject
     */
    public function getAccessValues(AccessValueObject $accessValueObject);


    /**
     * Your job is to save the value object values and return a bool indicating success.
     *
     * @param AccessValueObject $accessValueObject
     */
    public function setAccessValues(AccessValueObject $accessValueObject): bool;


    /**
     * Your job is to delete access records for the account return a bool indicating success.
     * @param $accountId
     * @return bool
     */
    public function deleteAccessValues($accountId): bool;

}