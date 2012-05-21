<?php
include 'private.php'; // passwords and DB info for the DB on caerphoto.com

if ($_SERVER['SERVER_NAME'] !== "caerphoto.com") {
    $db_conn = "mysql:host=localhost;dbname=caerpho_minipets;unix_socket=/tmp/mysql.sock";
    $username = "";
    $pw = "";
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

$db_cols = array(
    'short_name', // text (derived from long_name)
    'long_name',  // text
    'type',       // text (critter, mechanical etc.)
    'rarity',     // ?? text for now
    'img_src',    // text (URL)
    'x',          // integer (if actual coord is 52.4, store 524)
    'y',
    'ob_via',     // integer (1 = quest, 2 = vendor, 3 = drop, etc.)
    'loc',        // text (e.g. "Scarlet Monastery Graveyard")
    'info'        // Misc additional info.
);

$types = array(
    "Aquatic",
    "Beast",
    "Critter",
    "Dragonkin",
    "Elemental",
    "Flying",
    "Humanoid",
    "Magic",
    "Mechanical",
    "Undead"
);

$sources = array(
    "Drop",
    "Quest",
    "Vendor",
    "Profession",
    "Pet Battle",
    "Achievement",
    "World Event",
    "Promotion",
    "Trading Card Game",
    "Blizzard Pet Store"
);

$rarities = array(
    "Poor",
    "Common",
    "Uncommon",
    "Rare",
    "Epic",
    "Legendary"
);

?>
