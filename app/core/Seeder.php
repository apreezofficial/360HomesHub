<?php
class Seeder {
    private $db;

    public function __construct(){
        $seedModel = $this->model('Seed');
        $this->db = $seedModel->db;
    }

    public function model($model){
        // Require model file
        require_once APPROOT . '/models/' . $model . '.php';

        // Instatiate model
        return new $model();
    }

    public function seed() {
        $db_type = $this->db->getDbType();
        $sql = file_get_contents(APPROOT . '/../db/database.sql');

        if ($db_type === 'sqlite') {
            // Convert MySQL to SQLite syntax
            $sql = str_replace('`', '', $sql);
            $sql = str_replace('int(11) NOT NULL AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = str_replace('current_timestamp()', 'CURRENT_TIMESTAMP', $sql);
            $sql = str_replace('ENGINE=InnoDB DEFAULT CHARSET=utf8mb4', '', $sql);
        }

        try {
            $this->db->query("SELECT 1 FROM temporary_users LIMIT 1");
            $this->db->execute();
        } catch (PDOException $e) {
            // Table does not exist, so create it
            try {
                $this->db->query($sql);
                $this->db->execute();
            } catch (PDOException $e) {
                echo 'Could not seed database: ' . $e->getMessage();
            }
        }
    }
}
