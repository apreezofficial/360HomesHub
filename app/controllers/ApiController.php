<?php
  class ApiController {
    public function __construct(){
     
    }
    
    public function index(){
        $this->sendJsonResponse(['message' => 'Welcome to the 360-homeshub API']);
    }

    protected function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function model($model){
      // Require model file
      require_once __DIR__ . '/../models/' . $model . '.php';

      // Instatiate model
      return new $model();
    }
  }
