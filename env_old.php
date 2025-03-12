<?php
function loadEnv($path, $storeId = null) {
    // Load default env first
    if(file_exists($path)) {
        parseEnvFile($path);
    }

    //load the defaults first
    $base_env = dirname($path) . '/.env';
    if(file_exists($base_env)) {
        parseEnvFile($base_env);
    }

    // Load store-specific env if exists
    if($storeId !== null) {
        $storeEnvPath = dirname($path) . '/.env_' . $storeId;
        if(file_exists($storeEnvPath)) {
            parseEnvFile($storeEnvPath);
        }
    }
}

function parseEnvFile($path) {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}