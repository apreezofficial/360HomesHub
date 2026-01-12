<?php 
class Seed {
    public $db;

    public function __construct(){
        $this->db = new Database;
    }
}