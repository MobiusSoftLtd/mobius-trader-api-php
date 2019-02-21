<?php

class MobiusTrader_Cache
{
    private $_data = array();

    public function __construct($config)
    {
        $this->config = array_merge(array(
            'cache_enabled' => true,
            'cache_path' => '/tmp/mt7.cache',
            'cache_lifetime' => 60 * 5,
        ), $config);

        $this->load();
    }

    private function load()
    {
        if (!$this->enabled()) return;

        $path = $this->get_path();
        if (file_exists($path) && (filemtime($path) > (time() - $this->config['cache_lifetime']))) {
            $this->_data = json_decode(file_get_contents($path), true);
        }
    }

    public function enabled()
    {
        return $this->config['cache_enabled'];
    }

    public function get_path()
    {
        return $this->config['cache_path'];
    }

    private function save()
    {
        if (!$this->enabled()) return;

        $path = $this->get_path();
        file_put_contents($path, json_encode($this->_data), LOCK_EX);
    }

    public function get($key, $default_value = null)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : $default_value;
    }

    public function set($key, $value)
    {
        $this->_data[$key] = $value;
        $this->save();
    }
}
