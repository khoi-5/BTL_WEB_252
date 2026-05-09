<?php

class Section 
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAllSections() {
        $query = "SELECT * FROM product_section";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPublicSectionsWithProducts() {
        $query = "
            SELECT *
            FROM product_section
            WHERE section_status = 1
            ORDER BY section_id ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sections as &$section) {
            $section["products"] = $this->getSectionProducts((int)$section["section_id"]);
        }
        unset($section);

        return $sections;
    }

    private function getSectionProducts($sectionId) {
        $query = "
            SELECT
                pv.version_id,
                pv.product_id,
                p.product_name,
                p.brand,
                p.description,
                pv.sku,
                pv.version_name,
                pv.format_type,
                pv.language,
                pv.cover_type,
                pv.edition,
                pv.price,
                pv.stock_quantity,
                COALESCE(NULLIF(pv.image_url, ''), 'uploads/products/no_img.jpg') AS image_url,
                pv.version_status
            FROM product_section_details psd
            JOIN products p ON psd.product_id = p.product_id
            JOIN product_versions pv ON pv.version_id = (
                SELECT pv2.version_id
                FROM product_versions pv2
                WHERE pv2.product_id = p.product_id
                  AND pv2.version_status <> 'hidden'
                ORDER BY
                  CASE pv2.version_status
                    WHEN 'available' THEN 0
                    WHEN 'out_of_stock' THEN 1
                    ELSE 2
                  END,
                  pv2.version_id ASC
                LIMIT 1
            )
            WHERE psd.section_id = :section_id
            ORDER BY psd.display_order ASC, p.product_id ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([":section_id" => $sectionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSectionById($id) {
        $query = "
            SELECT * FROM product_section
            WHERE section_id = :section_id
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':section_id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSectionByName($name) {
        $query = "
            SELECT * FROM product_section
            WHERE section_name = :section_name
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':section_name' => $name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addSection($name, $description, $image, $status) {
        $query = 'INSERT INTO product_section (section_name, section_description, section_image, section_status) 
        VALUES(:section_name, :description, :section_image, :status)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':section_name' => $name,
            ':description' => $description,
            ':section_image' => $image,
            ':status' => $status
        ]);
        return $this->conn->lastInsertId();
    }

    public function updateSection($id, $name, $description, $image, $status) {
        $query = 'UPDATE product_section 
        SET section_name = :section_name, 
        section_description = :description, 
        section_image = :section_image, 
        section_status = :status 
        WHERE section_id = :section_id';

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':section_name' => $name,
            ':description' => $description,
            ':section_image' => $image,
            ':status' => $status,
            ':section_id' => $id
        ]);
    }

    public function deleteSection($id) {
        $query = 'DELETE FROM product_section WHERE section_id = :section_id';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':section_id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getFlashSaleById($id) {
        $query = "SELECT * FROM flash_sales WHERE flash_sale_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getFlashSaleByName($name) {
        $query = "SELECT * FROM flash_sales WHERE flash_sale_name = :name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':name' => $name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addFlashSale($name, $description, $image, $start_time, $end_time, $is_active) {
        $query = 'INSERT INTO flash_sales (flash_sale_name, flash_sale_description, flash_sale_image, flash_sale_start_time, flash_sale_end_time, flash_sale_is_active) VALUES(:name, :description, :image, :start_time, :end_time, :is_active)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':image' => $image,
            ':start_time' => $start_time,
            ':end_time' => $end_time,
            ':is_active' => $is_active
        ]);
        return $this->conn->lastInsertId();
    }

    public function updateFlashSale($id, $name, $description, $image, $start_time, $end_time, $is_active) {
        $query = 'UPDATE flash_sales 
        SET flash_sale_name = :name, 
        flash_sale_description = :description,
        flash_sale_image = :image,
        flash_sale_start_time = :start_time, 
        flash_sale_end_time = :end_time, 
        flash_sale_is_active = :is_active 
        WHERE flash_sale_id = :id';

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':image' => $image,
            ':start_time' => $start_time, 
            ':end_time' => $end_time,
            ':is_active' => $is_active,
            ':id' => $id
        ]);
        
    }

    public function deleteFlashSale($id) {
        $query = 'DELETE FROM flash_sales WHERE flash_sale_id = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
