<?php
include 'private.php'; // passwords and DB info for the DB on caerphoto.com

if (file_exists('../local.txt')) {
    $db_conn = "mysql:host=localhost;dbname=caerpho_minipets;unix_socket=/tmp/mysql.sock";
    $username = "";
    $pw = "";
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

$db_cols = array(
    'short_name', // text (derived from long_name)
    'long_name',  // text
    'type',       // integer (see $types below)
    'rarity',     // integer (see $rarities below)
    'img_src',    // text (URL)
    'x',          // integer (if actual coord is 52.4, store 524)
    'y',
    'ob_via',     // integer (see $sources below)
    'loc',        // text (e.g. "Scarlet Monastery Graveyard")
    'info',       // text; misc additional info, including flavour text.
    'tradeable',  // integer(1); whether the pet can be sold at the AH or not.
    'can_battle'  // integer(1); whether the pet can battle or not
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
