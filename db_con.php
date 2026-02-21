<?php

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "village_main_db2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    date_default_timezone_set("Asia/Bangkok");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$config_promptpay_id = "0857892632";
$config_account_names = [
    "ณัฐพล",
    "NUTTAPON",
    "NUTTAPON S",
    "ณัฐพล ท"
];

// API Key
$config_easyslip_api_key = "81b702bd-0977-443e-ab23-1f110cfb3d4d";

function calculatePoints($amount)
{
    return floor($amount * 1);
}
