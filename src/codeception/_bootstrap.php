<?php
use Symfony\Component\Yaml\Yaml;

// Check if Joomla is installed, because codeception will run this file for some commands like generate:cest
if (file_exists('/var/www/html/administrator')) {
    // Set the joomla version for tests
    $jversion = file_exists('/var/www/html/templates/beez_20') ? '25' : '34';

    $configPath = realpath(__DIR__ . '/../../codeception.yml');
    if (!file_exists($configPath)) {
        throw new Exception("File not found: codeception.yml");
    }
    $localConfig = Yaml::parse(file_get_contents($configPath));

    // Load the bootstrap file from Alledia Test Framework
    require_once __DIR__ . '/_bootstrap_joomla' . $jversion . '.php';
}
