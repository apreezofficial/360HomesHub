<?php
/*
 * API Core Class
 * Loads API routes
 */
class ApiCore {
  public function __construct(){
    // Load API routes
    require_once __DIR__ . '/../routes/api.php';
  }
}

