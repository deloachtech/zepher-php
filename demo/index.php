<?php

include "init.inc.php";

?>
<html lang="en">

<head>
    <title>Index</title>
    <link href="styles.css" rel="stylesheet">
</head>
<body>

<?php include "nav.inc.php"; ?>

<h1>Index</h1>

<pre>
    <?= print_r($zepher->getDomain(),1) ?>
    <?= print_r($zepher->getVersion(),1) ?>
</pre>

</body>

</html>



