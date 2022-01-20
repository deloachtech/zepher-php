<?php

use DeLoachTech\Zepher\FilesystemPersistence;
use DeLoachTech\Zepher\Zepher;

include "../src/AccessValueObject.php";
include "../src/PersistenceClassInterface.php";
include "../src/FeeProviderPersistenceInterface.php";
include "../src/FilesystemPersistence.php";
include "../src/Zepher.php";

$session = json_decode(file_get_contents('session.json'), true) ?? [];

$zepher = new Zepher($session['account']['id'], $session['account']['domain_id'], new FilesystemPersistence(), __DIR__);

function updateSession($session)
{
    file_put_contents('session.json', json_encode($session, JSON_PRETTY_PRINT));
}