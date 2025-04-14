<?php
// Load config
require_once('config.php');

// Manually load startup (for DIR_ constants)
require_once(DIR_SYSTEM . 'startup.php');

// Manually include your DB driver
require_once(DIR_SYSTEM . 'library/db/' . DB_DRIVER . '.php');


$db_hostname = 'localhost';
$db_username = 'root';
$db_password = 'Keeba69!';
$db_database = 'totalsafetygroup_oc';
$db_port = '3306';

$config = new Config();
$config->load('default');

$db = new DB($config->get('db_engine'), $db_hostname, $db_username, $db_password, $db_database, $db_port);
// Init DB manually
$dbclass = 'DB\\' . ucfirst(DB_DRIVER); // e.g., DB\Mysqli
$db = new $dbclass($db_hostname, $db_username, $db_password, $db_database, $db_port);

// Export GLOBAL settings (store_id = 0)
$global = $db->query("SELECT `key`, `value`, `serialized` FROM `" . DB_PREFIX . "setting` WHERE store_id = 0");
$globalData = [];

foreach ($global->rows as $row) {
    $value = $row['serialized'] ? json_decode($row['value'], true) : $row['value'];
    $globalData[$row['key']] = $value;
}

file_put_contents(DIR_SYSTEM . "config_store_0.json", json_encode($globalData, JSON_PRETTY_PRINT));
echo "Exported GLOBAL settings to config_store_0.json\n";

// Get all store IDs
$stores = $db->query("SELECT store_id FROM `" . DB_PREFIX . "store`");

foreach ($stores->rows as $store) {
    $store_id = (int)$store['store_id'];

    $query = $db->query("SELECT `key`, `value`, `serialized` FROM `" . DB_PREFIX . "setting` WHERE store_id = '{$store_id}'");

    $data = [];
    foreach ($query->rows as $row) {
        $value = $row['serialized'] ? json_decode($row['value'], true) : $row['value'];
        $data[$row['key']] = $value;
    }

    file_put_contents(DIR_SYSTEM . "config_store_{$store_id}.json", json_encode($data, JSON_PRETTY_PRINT));
    echo "Exported store_id {$store_id} to config_store_{$store_id}.json\n";
}
