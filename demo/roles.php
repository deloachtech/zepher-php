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

    $session['user']['roles'] = $_POST['roles'];
    updateSession($session);
}

$current = array_flip($session['user']['roles'] ?? []);
?>
<html lang="en">
<head>
    <title>Active Roles</title>
    <link href="styles.css" rel="stylesheet">
</head>

<body>

<?php include "nav.inc.php"; ?>

<h1>Active Roles</h1>

<p>Role ids can be human-readable (e.g. ROLE_SUPERUSER)</p>

<form method="post">

    <?php foreach ($zepher->getRoles()??[] as $id => $role) { ?>

    <div>
        <label>
            <input type="checkbox" class="check" name="roles[]" <?= isset($current[$id]) ? 'checked="checked"' : '' ?> value="<?= $id ?>"><?= $role['title'] ?>
        </label>
    </div>

    <?php } ?>
    <input class="button" type="submit">
</form>


</body>
</html>


