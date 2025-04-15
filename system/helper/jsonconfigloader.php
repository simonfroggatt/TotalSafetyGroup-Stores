<?php
function setupConfigFromJson($registry) {
    // Load config class
    $config = new Config();
    // Load custom JSON config loader
    require_once(DIR_SYSTEM . 'library/config/jsonloader.php');
    // Manually include your DB driver
    require_once(DIR_SYSTEM . 'library/db/' . DB_DRIVER . '.php');
    require_once(DIR_SYSTEM . 'helper/env.php');
    loadEnv(DIR_SYSTEM . '.env');

    $dbclass = 'DB\\' . ucfirst(DB_DRIVER);
    $db_hostname = $_ENV['DB_HOSTNAME'];
    $db_username = $_ENV['DB_USERNAME'];
    $db_password = $_ENV['DB_PASSWORD'];
    $db_database = $_ENV['DB_DATABASE'];
    $db_port = (int)$_ENV['DB_PORT'];

    $db = new $dbclass($db_hostname, $db_username, $db_password, $db_database, $db_port);

    // Detect store_id based on domain
    $store_id = 0;

   /* if ($this->request->server['HTTPS']) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`ssl`, 'www.', '') = '" . $this->db->escape('https://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
        $tmp = "SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`ssl`, 'www.', '') = '" . $this->db->escape('https://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'";
    } else {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`url`, 'www.', '') = '" . $this->db->escape('http://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
    }

    if (isset($this->request->get['store_id'])) {
        $this->config->set('config_store_id', (int)$this->request->get['store_id']);
    } else if ($query->num_rows) {
        $this->config->set('config_store_id', $query->row['store_id']);
    } else {
        $this->config->set('config_store_id', 0);
    }

    if (!$query->num_rows) {
        $this->config->set('config_url', HTTP_SERVER);
        $this->config->set('config_ssl', HTTPS_SERVER);
    }

    */

    if($_SERVER['HTTPS']) {

        $sql = "SELECT store_id FROM `" . DB_PREFIX . "store` WHERE REPLACE(`ssl`, 'www.', '') = '" . 'http://' .str_replace('www.', '', $_SERVER['HTTP_HOST']) .  rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/' . "'";
    } else {
        $sql = "SELECT store_id FROM `" . DB_PREFIX . "store` WHERE REPLACE(`url`, 'www.', '') = '" . 'http://'. str_replace('www.', '', $_SERVER['HTTP_HOST']) .  rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/' ."'";
    }

    $query = $db->query($sql);
    if ($query->num_rows) {
        $store_id = (int)$query->row['store_id'];
    }

    // Load JSON config into the config object
    $jsonConfig = new ConfigJsonLoader();
    $jsonConfig->load($config, $store_id);

    // Inject config into registry
    $registry->set('config', $config);

    // Optionally inject db into registry if not already done
    $registry->set('db', $db);
}