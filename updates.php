<?php

include "config.php";

function get_last_update() {
    global $db_conn, $username, $pw;

    $dbh = new PDO($db_conn, $username, $pw);
    $query = $dbh->prepare("select date, value from updates order by date desc limit 1");
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    unset($dbh);

    return $result;
}

$last_update = get_last_update();
$last_update_date = isset($last_update['date']) ?
    gmdate("D, d M Y, H:i", $last_update['date']) :
    gmdate("D, d M Y, H:i");

$last_update_value = isset($last_update['value']) ? $last_update['value'] : "None";
