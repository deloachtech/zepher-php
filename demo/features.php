<?php

include "init.inc.php";

$roles = $session['user']['roles'];
?>
<html lang="en">
<head>
    <title>Active Features</title>
    <link href="styles.css" rel="stylesheet">
</head>

<body>

<?php include "nav.inc.php"; ?>

<h1>Active Features</h1>

<p>Feature ids SHOULD be human-readable (e.g. FEATURE_FOO); <code>$zepher->userCanAccess('FEATURE_FOO')</code></p>
<p>Permission ids SHOULD be human-readable (e.g. PERMISSION_READ); <code>$zepher->userCanAccess('FEATURE_FOO', 'PERMISSION_READ')</code></p>

<table>

    <thead>
    <tr>
        <th>Feature</th>
        <th>userCanAccess</th>
        <th>Permissions</th>
    </tr>
    </thead>

    <?php foreach ($zepher->getVersion()['features']??[] as $feature) { ?>

        <tr>
            <td><?= $feature ?></td>
            <td><?= $zepher->userCanAccess($feature) ? 'true':'false' ?></td>
            <td><?= implode(", ", $zepher->getUserFeaturePermissions($feature,$roles)) ?></td>
        </tr>
    <?php } ?>

</table>

</body>
</html>


