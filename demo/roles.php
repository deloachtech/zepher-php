<?php

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

    <?php foreach ($zepher->getRoles() as $role) { ?>

    <div>
        <label>
            <input type="checkbox" class="check" name="roles[]" <?= isset($current[$role['id']]) ? 'checked="checked"' : '' ?> value="<?= $role['id'] ?>"><?= $role['title'] ?>
        </label>
    </div>

    <?php } ?>
    <input class="button" type="submit">
</form>


</body>
</html>


