<?php

include "init.inc.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $current = $zepher->getAccountVersionId();

    if($_POST['version'] != $current) {

        $zepher->setAccountVersionId($_POST['version']);

        // The default FilesystemPersistence class manages the version. For completeness, we'll update the "database"
        $database['account']['version_id'] = $_POST['version'];
        updateDatabase($database);
    }

    header("location: versions.php");
}

$current = $zepher->getVersion()['id'];
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
                <?php foreach ($zepher->getDomainVersions() as $version) { ?>
                    <option <?= $current == $version['id'] ? 'selected="selected"' : '' ?> value="<?= $version['id'] ?>"><?= $version['title'] ?></option>
                <?php } ?>
            </select>
        </label>
    </div>
    <input class="button" type="submit">
</form>


</body>
</html>


