<?php

use DeLoachTech\Zepher\FilesystemPersistence;
use DeLoachTech\Zepher\Zepher;

include "../src/AccessValueObject.php";
include "../src/PersistenceClassInterface.php";
include "../src/FeeProviderPersistenceInterface.php";
include "../src/FilesystemPersistence.php";
include "../src/Zepher.php";

$database = json_decode(file_get_contents('database.json'), true) ?? [];

$zepher = new Zepher($database['account']['id'], $database['account']['domain_id'], new FilesystemPersistence(), __DIR__);

function updateDatabase($database)
{
    file_put_contents('database.json', json_encode($database, JSON_PRETTY_PRINT));
}