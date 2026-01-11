<?php
class TemporaryUser {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    public function createTemporaryUser($data){
        $this->db->query('INSERT INTO temporary_users (email, phone, password, first_name, last_name, bio) VALUES (:email, :phone, :password, :first_name, :last_name, :bio)');
        // Bind values
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':password', $data['password']);
        $this->db->bind(':first_name', $data['first_name']);
        $this->db->bind(':last_name', $data['last_name']);
        $this->db->bind(':bio', $data['bio']);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function updatePersonalDetails($data){
        $this->db->query('UPDATE temporary_users SET first_name = :first_name, last_name = :last_name, bio = :bio WHERE email = :email OR phone = :phone');
        // Bind values
        $this->db->bind(':first_name', $data['first_name']);
        $this->db->bind(':last_name', $data['last_name']);
        $this->db->bind(':bio', $data['bio']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function findTemporaryUser($data){
        $this->db->query('SELECT * FROM temporary_users WHERE email = :email OR phone = :phone ORDER BY created_at DESC LIMIT 1');
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);

        $row = $this->db->single();

        // Check row
        if($this->db->rowCount() > 0){
            return $row;
        } else {
            return false;
        }
    }
    
    public function updateAddress($data){
        $this->db->query('UPDATE temporary_users SET address = :address, city = :city, state = :state, country = :country WHERE email = :email OR phone = :phone');
        // Bind values
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':city', $data['city']);
        $this->db->bind(':state', $data['state']);
        $this->db->bind(':country', $data['country']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function deleteTemporaryUser($id){
        $this->db->query('DELETE FROM temporary_users WHERE id = :id');
        $this->db->bind(':id', $id);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function updateProfilePhoto($data){
        $this->db->query('UPDATE temporary_users SET profile_photo = :profile_photo WHERE email = :email OR phone = :phone');
        // Bind values
        $this->db->bind(':profile_photo', $data['profile_photo']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }
}
