<?php

namespace DeLoachTech\Zepher;

interface PersistenceClassInterface
{

    /**
     * The current zepher object file being used. (In case you need it.)
     *
     * @param $objectFile
     */
    public function objectFile($objectFile);



    /**
     * Your job is to create a new access record with the values and return a bool indicating success.
     *
     * @param AccessValueObject $accessValueObject
     */
    public function createAccessRecord(AccessValueObject $accessValueObject);


    /**
     * Your job is to set all the current object values from your data storage. (The account id is already set in the VO.)
     * If there are no values, a new access record will be created for the account id.
     *
     * @param AccessValueObject $accessValueObject
     */
    public function getCurrentAccessRecord(AccessValueObject $accessValueObject);


    /**
     * Your job is to update the current access record with the new values and return a bool indicating success.
     *
     * @param AccessValueObject $accessValueObject
     */
    public function updateAccessRecord(AccessValueObject $accessValueObject): bool;


    /**
     * Your job is to delete access records for the account return a bool indicating success.
     * @param $accountId
     * @return bool
     */
    public function deleteAccessRecords($accountId): bool;

}