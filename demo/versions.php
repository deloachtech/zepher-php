<?php

include "init.inc.php";

$current = $zepher->getVersion();
?>
<html lang="en">
<head>
    <title>Active Versions</title>
    <link href="styles.css" rel="stylesheet">
</head>

<body>

<?php include "nav.inc.php"; ?>

<h1>Active Versions</h1>

<table>

    <thead>
    <tr>
        <th>Version</th>
        <th>Description</th>
        <th>Fees</th>
        <th>Fee Description</th>
    </tr>
    </thead>

    <?php foreach ($zepher->getDomainVersions() as $version) { ?>

        <tr>
            <td><?= $version['title'] ?><?= $current['id'] == $version['id'] ? ' *' : '' ?></td>
            <td><?= $version['desc'] ?></td>
            <td><?= implode(', ', $version['fees']) ?></td>
            <td><?= $version['fee_desc'] ?></td>

        </tr>
    <?php } ?>

</table>

<a href="version.php">Change version</a>
</body>
</html>


