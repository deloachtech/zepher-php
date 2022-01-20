<?php

include "init.inc.php";
?>
<html lang="en">
<head>
    <title>Active Modules</title>
    <link href="styles.css" rel="stylesheet">
</head>

<body>

<?php include "nav.inc.php"; ?>

<h1>Active Modules</h1>

<p>Note: Module ids SHOULD be human-readable (e.g. MODULE_FOO); <code>$zepher->moduleIsActive('MODULE_FOO')</code></p>

<table>

    <thead>
    <tr>
        <th>Module</th>
    </tr>
    </thead>

    <?php foreach ($zepher->getVersion()['modules'] as $module) { ?>

        <tr>
            <td><?= $module ?></td>
        </tr>
    <?php } ?>

</table>

</body>
</html>


