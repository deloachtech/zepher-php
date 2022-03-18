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
?>
<html lang="en">
<head>
    <title>Domain Network</title>
    <link href="styles.css" rel="stylesheet">
</head>

<body>

<?php include "nav.inc.php"; ?>

<h1>Domain Network</h1>


<table>

    <thead>
    <tr>
        <th>Id</th>
        <th>Title</th>
    </tr>
    </thead>

    <?php foreach ($zepher->getDomainNetwork() as $domain) { ?>

        <tr>
            <td><?= $domain['id'] ?></td>
            <td><?= $domain['title'] ?></td>
        </tr>
    <?php } ?>

</table>

</body>
</html>


