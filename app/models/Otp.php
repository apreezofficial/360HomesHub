<?php
class Otp {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    public function createOtp($data){
        $this->db->query('INSERT INTO otps (email, phone, otp, expires_at) VALUES (:email, :phone, :otp, :expires_at)');
        // Bind values
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':otp', $data['otp']);
        $this->db->bind(':expires_at', $data['expires_at']);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function findOtp($data){
        $this->db->query('SELECT * FROM otps WHERE (email = :email OR phone = :phone) AND otp = :otp AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1');
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':otp', $data['otp']);

        $row = $this->db->single();

        // Check row
        if($this->db->rowCount() > 0){
            return $row;
        } else {
            return false;
        }
    }

    public function deleteOtp($id){
        $this->db->query('DELETE FROM otps WHERE id = :id');
        $this->db->bind(':id', $id);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }
}
