<?php

// Load Config
require_once __DIR__ . '/../config/config.php';
// Load Composer Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Autoload Core Libraries
spl_autoload_register(function($className){
  require_once __DIR__ . '/core/' . $className . '.php';
});

// Seed the database
$seeder = new Seeder();
$seeder->seed();
