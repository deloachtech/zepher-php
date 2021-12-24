<?php

namespace DeLoachTech\AppAccessControl;

class AppAccessControl
{
    private $config;


    public function __construct(string $version, string $configFile = __DIR__ . '/config.json')
    {
        if (file_exists($configFile)) {
            $this->config = json_decode($configFile)['data'][$version] ?? [];
        }
    }

    public function getRoles()
    {
        return $this->config['roles'];
    }

}