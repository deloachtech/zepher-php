<?php

include "init.inc.php";

$records = json_decode(file_get_contents('zepher.access.json'), true)[$session['account']['id']] ?? [];

usort($records, function ($a, $b) {
    return $b['activated'] <=> $a['activated']; // Sort descending
});

?>
<html lang="en">
<head>
    <title>Access</title>
    <link href="styles.css" rel="stylesheet">
</head>

<body>

<?php include "nav.inc.php"; ?>

<h1>Access</h1>

<table>

    <thead>
    <tr>
        <th>Account Id</th>
        <th>Title</th>
        <th>Id</th>
        <th>Activated (Descending Order)</th>
    </tr>
    </thead>

    <?php foreach ($records as $record) {
        $version = $zepher->getVersionById($record['version_id']);
        ?>
        <tr>
            <td><?= $session['account']['id'] ?></td>
            <td><?= $version['title'] ?></td>
            <td><?= $version['id'] ?></td>
            <td><?= $record['activated'] ?></td>
        </tr>
    <?php } ?>

</table>

</body>
</html>


