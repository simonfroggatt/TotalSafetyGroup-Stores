<?php

class ConfigJsonLoader
{
    public function load($config, $store_id = 0)
    {
        // Load global settings first
        $this->loadFile($config, DIR_SYSTEM . 'config_store_0.json');

        // Then load store-specific overrides if store_id > 0
        if ($store_id > 0) {
            $this->loadFile($config, DIR_SYSTEM . "config_store_{$store_id}.json");
        }
    }

    private function loadFile($config, $file)
    {
        if (!file_exists($file)) return;

        $json = file_get_contents($file);
        $data = json_decode($json, true);

        if (!is_array($data)) return;

        foreach ($data as $key => $value) {
            $config->set($key, $value);
        }
    }
}
