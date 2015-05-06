<?php
require_once 'simple_html_dom.php';
require_once 'func.php';


if (PHP_SAPI == 'cli') {

    if (count($argv) == 1) {
        echo "pass filename or url with html\ne.g. php index.php http://google.com\n";
    } else {
        $file = $argv[1];
        $obj = file_get_html($file);
        if ($obj) {
            buildChildrenList($obj->root);
        } else {
            exit(1);
        }
    }
    exit;
}

if (!empty($_POST['data'])) {
    /** @var simple_html_dom $obj */
//    $obj = file_get_html('data.txt');
    $obj = str_get_html($_POST['data']);

    echo '<pre>';
    buildChildrenList($obj->root);
    exit;
}

?>
<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>Get Html Structure</title>
</head>
<body>

<form action="" method="post">
    <textarea name="data" id="" cols="30" rows="10" style="width: 90%; height: 300px;"></textarea><br />
    <input type="submit" value="Get Html Structure"/>
</form>

</body>
</html>

