<?php
/**
 * This file is part of the deloachtech/zepher-php package.
 *
 * (c) DeLoach Tech, LLC
 * https://deloachtech.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Simulates a full installation.

use DeLoachTech\Zepher\FilesystemPersistence;
use DeLoachTech\Zepher\Zepher;

include "../src/AccessValueObject.php";
include "../src/PersistenceClassInterface.php";
include "../src/FeeProviderPersistenceInterface.php";
include "../src/FilesystemPersistence.php";
include "../src/Zepher.php";

$session = json_decode(file_get_contents('session.json'), true) ?? [];


$zepher = new Zepher(
    $session['account']['domain_id'],
    $session['account']['id'],
    $session['user']['roles'],
    new FilesystemPersistence(),
    'zepher.json'
);

function updateSession($session)
{
    file_put_contents('session.json', json_encode($session, JSON_PRETTY_PRINT));
}