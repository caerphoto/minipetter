<?php
session_start();

include "private.php";

$password = $_POST['password'];

header('Content-Type: application/json');

if ($_POST['action'] == 'login') {
    if ($password == $login_password) {
        $_SESSION['logged_in'] = 'true';
    } else {
        $_SESSION['logged_in'] = 'false';
        header('HTTP/1.0 403 Forbidden');
    }
} else {
    $_SESSION['logged_in'] = 'false';
}
?>
