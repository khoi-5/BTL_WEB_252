<?php

class WebInfo {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function updateWebInfo($site_name, $description, $logo, $favicon, $hotline, $email, $address, $footer_text) {
        $query = 'UPDATE web_info SET site_name = :site_name, description = :description, logo = :logo, favicon = :favicon, hotline = :hotline, email = :email, address = :address, footer_text = :footer_text';
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':site_name' => $site_name,
            ':description' => $description,
            ':logo' => $logo,
            ':favicon' => $favicon,
            ':hotline' => $hotline,
            ':email' => $email,
            ':address' => $address,
            ':footer_text' => $footer_text
        ]);
    }

    public function getWebInfo() {
        $query = 'SELECT * FROM web_info';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}