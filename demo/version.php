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

include "init.inc.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $current = $zepher->getAccessValueObject();

    if($_POST['version'] != $current) {

        $current->setVersionId($_POST['version']);

        $zepher->updateAccessRecord($current);

        // The default FilesystemPersistence class manages the version. For completeness, we'll update the "database"
        $session['account']['version_id'] = $_POST['version'];
        updateSession($session);
    }

    header("location: versions.php");
}

$current = key($zepher->getVersion());


?>
<html lang="en">
<head>
    <title>Version</title>
    <link href="styles.css" rel="stylesheet">
</head>

<body>

<?php include "nav.inc.php"; ?>

<h1>Select Version</h1>

<form method="post">

    <div>
        <label>
            <select name="version" class="select">
                <?php foreach ($zepher->getDomainVersions() as $id => $version) { ?>
                    <option <?= $current == $id ? 'selected="selected"' : '' ?> value="<?= $id ?>"><?= $version['title'] ?></option>
                <?php } ?>
            </select>
        </label>
    </div>
    <input class="button" type="submit">
</form>


</body>
</html>


