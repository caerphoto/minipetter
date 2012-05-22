<?php
/* Simple script that responds with the image file of the specified pet, if 
 * found, otherwise the placeholder image.
 * Also sets appropriate (I hope) headers re: cache.
 */

$name = $_GET['name'];

function dumpImage ($name) {
    header("Last-Modified: " . gmdate("D, d M Y H:i:s \G\M\T", filemtime($name)));
    header("Content-Length: " . filesize($name));
    $fp = @fopen($name, 'rb');
    fpassthru($fp);
}

if ($name === "placeholder") {
    $name = "screenshots/placeholder.png";
    header("Content-Type: image/png");
    header("Cache-Control: max-age=86400"); // 24 hours
    dumpImage($name);

} else {
    $name = "screenshots/" . $name . ".jpg";

    if (file_exists($name)) {
        $a_week_from_now = time() + 604800;
        header("Content-Type: image/jpeg");
        header("Cache-Control: max-age=604800"); // 1 week
        header("Expires: " . gmdate("D, d M Y H:i:s \G\M\T", $a_week_from_now));
        dumpImage($name);

    } else {
        header("HTTP/1.1 307 Temporary Redirect");
        header("Location: img.php?name=placeholder");
    }
}
