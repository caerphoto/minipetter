<?php

include "config.php";

function get_all_minipets() {
    global $db_conn, $username, $pw, $db_cols;

    // gzip output, since it's quite big.
    ob_start("ob_gzhandler");

    $dbh = new PDO($db_conn, $username, $pw);
    $query = $dbh->prepare("select " .
        join(",", $db_cols) .
        " from minipets order by short_name;");
    $query->execute();
    $results = array();

    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        array_push($results, $row);
    }

    unset($dbh);

    return json_encode($results);
}


function check_auth() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != "true") {
        header('HTTP/1.0 403 Forbidden');
        exit;
    }
}

function get_pet($short_name) {
    global $db_conn, $username, $pw, $db_cols;

    $dbh = new PDO($db_conn, $username, $pw);
    $query = $dbh->prepare(
        "select " .
        join(",", $db_cols) .
        " from minipets where short_name = :short_name;"
    );

    $query->bindParam("short_name", $short_name);
    $result = $query->execute();

    if (!$result) {
        header('HTTP/1.0 500');
        $error = $query->errorInfo();
        var_dump($error);
    }

    return $query->fetch(PDO::FETCH_ASSOC);
}

function bind_params(&$query, &$new_pet) {
    global $db_cols;

    foreach ($db_cols as $col) {
        switch ($col) {
        case "x":
        case "y":
        case "type":
        case "rarity":
        case "ob_via":
            // Work around a PDO bug whereby PDO::PARAM_INT is ignored if the
            // value being bound is not an integer. MySQL doesn't complain about
            // this, but Postgres does.
            $new_pet[$col] = intval($new_pet[$col], 10);
            $query->bindParam(":" . $col, $new_pet[$col], PDO::PARAM_INT);
            break;
        default:
            $query->bindParam(":" . $col, $new_pet[$col]);
        }
    }
}

function update_pet(&$new_pet) {
    global $db_conn, $username, $pw, $db_cols;

    $dbh = new PDO($db_conn, $username, $pw);

    function make_sets($value) {
        return $value . " = :" . $value;
    };

    $params = array_map("make_sets", $db_cols);
    $params = join(",", $params);
    $query = $dbh->prepare(
        "update minipets set " .
        $params .
        " where short_name = :short_name;"
    );

    bind_params($query, $new_pet);

    $result = $query->execute();
    unset($dbh);

    if (!$result) {
        header('HTTP/1.0 500');
        $error = $query->errorInfo();
        var_dump($error);
    }

    // Ensure client data matches server by returning the pet data after it's
    // been run through the DB.
    $pet = get_pet($new_pet['short_name']);
    return json_encode($pet);
}

function add_pet() {
    check_auth();

    global $db_conn, $username, $pw, $db_cols;

    // Validate query parameters
    $valid = true;
    $new_pet = array();

    if (get_magic_quotes_gpc()) {
        foreach ($db_cols as $col) {
            $new_pet[$col] = isset($_REQUEST[$col]) ?
                stripslashes($_REQUEST[$col]) :
                null;
        }
    } else {
        foreach ($db_cols as $col) {
            $new_pet[$col] = isset($_REQUEST[$col]) ?
                $_REQUEST[$col] :
                null;
        }
    }

    if (!$new_pet["short_name"]) {
        $valid = false;
    }

    if (!$new_pet["long_name"]) {
        $valid = false;
    }

    if (!$valid) {
        header('HTTP/1.0 400 Bad Request');
        return json_encode(false);
    }

    $dbh = new PDO($db_conn, $username, $pw);

    $query = $dbh->prepare("select id from minipets where short_name=:short_name;");
    $query->bindParam(":short_name", $new_pet["short_name"]);
    $query->execute();

    $result = $query->fetch();

    unset($dbh);

    if ($result) {
        // Pet already exists, so update it
        return update_pet($new_pet);
    } else {
        // Create a new pet entry
        $dbh = new PDO($db_conn, $username, $pw);
        function make_param($value) {
            return ":" . $value;
        };
        $params = array_map("make_param", $db_cols);

        $query_str = array("insert into minipets (");
        array_push($query_str, join(",", $db_cols), ") values (");
        array_push($query_str, join(",", $params), ");");
        $query_str = join("", $query_str);

        $query = $dbh->prepare($query_str);
        bind_params($query, $new_pet);
        $result = $query->execute();

        unset($dbh);

        if (!$result) {
            header('HTTP/1.0 500');
            $error = $query->errorInfo();
            var_dump($error);
        }

        $result = get_pet($new_pet['short_name']);
        return json_encode($result); // success
    }
}

function delete_pet() {
    check_auth();

    global $db_conn, $username, $pw;

    $short_name = isset($_REQUEST['short_name']) ? $_REQUEST['short_name'] : null;

    if (!$short_name) {
        header('HTTP/1.0 400 Bad Request');
        return json_encode(false);
    }

    $dbh = new PDO($db_conn, $username, $pw);
    $query = $dbh->prepare("delete from minipets where short_name = :short_name;");
    $query->bindParam(":short_name", $short_name);

    $result = $query->execute();

    unset($dbh);

    if (!$result) {
        header('HTTP/1.0 500');
    }

    return json_encode($result);
}

function add_update() {
    check_auth();

    global $db_conn, $username, $pw;

    $value = isset($_REQUEST['value']) ? $_REQUEST['value'] : null;
    $date = time();

    if (!$value) {
        header("HTTP/1.0 400 Bad Request");
        return json_encode(false);
    }

    $dbh = new PDO($db_conn, $username, $pw);
    $query = $dbh->prepare("insert into updates (date, value) values (:date, :value);");
    $query->bindParam(":date", $date);
    $query->bindParam(":value", $value);

    $result = $query->execute();

    unset($dbh);

    if (!$result) {
        header('HTTP/1.0 500');
    }

    $result = array(
        "date" => gmdate("D, d M Y, H:i", $date),
        "value" => $value
    );

    return json_encode($result);
}

$req_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

session_start();
header('Content-Type: application/json');

if ($req_method == "POST") {
    if ($action === "add_update") {
        echo add_update();
    } else {
        echo add_pet();
    }

} else {
    switch ($action) {
        case "delete":
            echo delete_pet();
            break;
        default:
            echo get_all_minipets();
    }
}

?>
