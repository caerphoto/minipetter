<?php
/* Simple script that responds with the image file of the specified pet, if 
 * found, otherwise the placeholder image.
 * Also sets appropriate (I hope) headers re: cache.
 */

$name = "screenshots/" . $_GET['name'] . ".jpg";

if (file_exists($name)) {
    header("Cache-Control: max-age=86400"); // 24 hours
} else {
    $name = "screenshots/placeholder.jpg";
    header("Cache-Control: max-age=604800"); // 1 week
}

header("Last-Modified: " . gmdate("D, d M Y H:i:s \G\M\T", filemtime($name)));

$a_week_from_now = time() + 604800;
header("Expires: " . gmdate("D, d M Y H:i:s \G\M\T", $a_week_from_now));

$fp = @fopen($name, 'rb');

header("Content-Type: image/jpeg");
header("Content-Length: " . filesize($name));

fpassthru($fp);
